<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\ProduitPanier;
use App\Entity\Reservation;
use App\Entity\ReservationGuest;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/history', name: 'app_reservation_history', methods: ['GET'])]
    public function history(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findBy([], ['dateReservation' => 'DESC']);

        return $this->render('reservation/history.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/create', name: 'app_reservation_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        HebergementRepository $hebergementRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        /**
         * Si l'utilisateur arrive en GET sur /reservation/create
         * (ex: bouton, lien, formulaire sans method="post", refresh navigateur),
         * on le redirige proprement vers la page front au lieu de laisser Symfony
         * essayer de résoudre une entité ou afficher une erreur.
         */
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('app_hebergement_front');
        }

        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('create_reservation', $token)) {
            $this->addFlash('error', 'Invalid reservation request.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $hotelId = (int) $request->request->get('hotelId');
        $nomClient = trim((string) $request->request->get('nomClient'));
        $emailClient = trim((string) $request->request->get('emailClient'));
        $dateCheckinRaw = trim((string) $request->request->get('dateCheckin'));
        $dateCheckoutRaw = trim((string) $request->request->get('dateCheckout'));
        $guestsCount = max(1, (int) $request->request->get('guestsCount', 1));
        $roomsCount = max(1, (int) $request->request->get('roomsCount', 1));
        $occupancy = strtoupper(trim((string) $request->request->get('occupancy', 'DOUBLE')));
        $roomTypeRaw = trim((string) $request->request->get('roomType', 'Standard'));

        $guestNames = $request->request->all('guestNames');
        $guestBirthDates = $request->request->all('guestBirthDates');

        $hebergement = $hebergementRepository->find($hotelId);

        if (!$hebergement) {
            $this->addFlash('error', 'Selected hotel not found.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        if ($nomClient === '') {
            $this->addFlash('error', 'Full name is required.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        if ($dateCheckinRaw === '' || $dateCheckoutRaw === '') {
            $this->addFlash('error', 'Check-in and check-out dates are required.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $dateCheckin = \DateTime::createFromFormat('Y-m-d', $dateCheckinRaw) ?: null;
        $dateCheckout = \DateTime::createFromFormat('Y-m-d', $dateCheckoutRaw) ?: null;

        if (!$dateCheckin || !$dateCheckout) {
            $this->addFlash('error', 'Invalid booking dates.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $today = new \DateTime('today');

        if ($dateCheckin < $today) {
            $this->addFlash('error', 'You cannot reserve a past check-in date.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        if ($dateCheckout <= $dateCheckin) {
            $this->addFlash('error', 'Check-out must be after check-in.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $allowedOccupancy = ['SINGLE', 'DOUBLE'];
        if (!in_array($occupancy, $allowedOccupancy, true)) {
            $occupancy = 'DOUBLE';
        }

        $roomType = $this->normalizeRoomType($roomTypeRaw);

        $perRoomCapacity = $occupancy === 'SINGLE' ? 1 : 2;
        $maxGuests = $roomsCount * $perRoomCapacity;

        if ($guestsCount > $maxGuests) {
            $this->addFlash('error', 'Guests exceed room occupancy capacity.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $prixParNuit = (float) ($hebergement->getPrixParNuit() ?? 0);
        $pricing = $this->calculateTotal(
            $prixParNuit,
            $dateCheckin,
            $dateCheckout,
            $roomsCount,
            $roomType
        );

        $reservation = new Reservation();
        $reservation
            ->setHebergement($hebergement)
            ->setNomClient($nomClient)
            ->setEmailClient($emailClient !== '' ? $emailClient : null)
            ->setDateCheckin($dateCheckin)
            ->setDateCheckout($dateCheckout)
            ->setStatut('CONFIRMED')
            ->setPrixTotal($pricing['total'])
            ->setDateReservation(new \DateTime())
            ->setGuestsCount($guestsCount)
            ->setRoomsCount($roomsCount)
            ->setOccupancy($occupancy)
            ->setRoomType($roomType);

        $entityManager->persist($reservation);

        foreach ($guestNames as $index => $guestName) {
            $guestName = trim((string) $guestName);

            if ($guestName === '') {
                continue;
            }

            $guest = new ReservationGuest();
            $guest->setReservation($reservation);
            $guest->setFullName($guestName);

            $birthDateRaw = isset($guestBirthDates[$index]) ? trim((string) $guestBirthDates[$index]) : '';
            if ($birthDateRaw !== '') {
                $birthDate = \DateTime::createFromFormat('Y-m-d', $birthDateRaw);
                if ($birthDate instanceof \DateTimeInterface) {
                    $guest->setBirthDate($birthDate);
                }
            }

            $entityManager->persist($guest);
        }

        $entityManager->flush();

        // ── Add hebergement to cart ──
        $panier = $entityManager->getRepository(Panier::class)->findOneBy([
            'userId' => 1, 'statut' => 'ACTIF',
        ], ['id' => 'DESC']);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUserId(1);
            $panier->setStatut('ACTIF');
            $panier->setDateCreation(new \DateTime());
            $panier->setDateModification(new \DateTime());
            $panier->setMontantTotalHt('0');
            $panier->setMontantTva('0');
            $panier->setMontantTtc('0');
            $panier->setMontantReduction('0');
            $entityManager->persist($panier);
            $entityManager->flush();
        }

        $cartItem = new ProduitPanier();
        $cartItem->setPanierId((int) $panier->getId());
        $cartItem->setProduitId((int) $hebergement->getId());
        $cartItem->setTypeProduit('HEBERGEMENT');
        $cartItem->setDateAjout(new \DateTime());
        $nights = max(1, (int) $dateCheckin->diff($dateCheckout)->days);
        $cartItem->setQuantite($nights);
        $cartItem->setPrixUnitaire((string) ($hebergement->getPrixParNuit() ?? 0));
        $cartItem->setPrixTotalLigne(number_format($pricing['total'], 2, '.', ''));
        $entityManager->persist($cartItem);

        // Refresh panier totals
        $allPanierItems = $entityManager->getRepository(ProduitPanier::class)->findBy(['panierId' => $panier->getId()]);
        $subtotal = 0.0;
        foreach ($allPanierItems as $pi) {
            $line = (float) ($pi->getPrixTotalLigne() ?? 0);
            if ($line <= 0) {
                $line = (float) ($pi->getPrixUnitaire() ?? 0) * (int) ($pi->getQuantite() ?? 1);
            }
            $subtotal += $line;
        }
        // Add current item
        $subtotal += (float) $pricing['total'];
        $taxes = $subtotal * 0.10;
        $total = $subtotal + $taxes;
        $panier->setMontantTotalHt(number_format($subtotal, 2, '.', ''));
        $panier->setMontantTva(number_format($taxes, 2, '.', ''));
        $panier->setMontantTtc(number_format($total, 2, '.', ''));
        $panier->setDateModification(new \DateTime());
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Reservation confirmed for %s. Total: $%s — Added to cart!',
                $hebergement->getNom(),
                number_format($pricing['total'], 1, '.', '')
            )
        );

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');

            if (!$this->isCsrfTokenValid('edit_reservation_'.$reservation->getId(), $token)) {
                $this->addFlash('error', 'Invalid edit request.');
                return $this->redirectToRoute('app_reservation_history');
            }

            $nomClient = trim((string) $request->request->get('nomClient', ''));
            $emailClient = trim((string) $request->request->get('emailClient', ''));
            $dateCheckinRaw = trim((string) $request->request->get('dateCheckin', ''));
            $dateCheckoutRaw = trim((string) $request->request->get('dateCheckout', ''));
            $guestsCount = max(1, (int) $request->request->get('guestsCount', 1));
            $roomsCount = max(1, (int) $request->request->get('roomsCount', 1));
            $occupancy = strtoupper(trim((string) $request->request->get('occupancy', 'DOUBLE')));
            $roomTypeRaw = trim((string) $request->request->get('roomType', 'Standard'));
            $statut = strtoupper(trim((string) $request->request->get('statut', 'CONFIRMED')));

            if ($nomClient === '') {
                $this->addFlash('error', 'Full name is required.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            $dateCheckin = \DateTime::createFromFormat('Y-m-d', $dateCheckinRaw) ?: null;
            $dateCheckout = \DateTime::createFromFormat('Y-m-d', $dateCheckoutRaw) ?: null;

            if (!$dateCheckin || !$dateCheckout) {
                $this->addFlash('error', 'Invalid booking dates.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            $today = new \DateTime('today');

            if ($dateCheckin < $today) {
                $this->addFlash('error', 'You cannot reserve a past check-in date.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            if ($dateCheckout <= $dateCheckin) {
                $this->addFlash('error', 'Check-out must be after check-in.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            $allowedOccupancy = ['SINGLE', 'DOUBLE'];
            if (!in_array($occupancy, $allowedOccupancy, true)) {
                $occupancy = 'DOUBLE';
            }

            $allowedStatus = ['CONFIRMED', 'PENDING', 'CANCELLED'];
            if (!in_array($statut, $allowedStatus, true)) {
                $statut = 'CONFIRMED';
            }

            $roomType = $this->normalizeRoomType($roomTypeRaw);

            $perRoomCapacity = $occupancy === 'SINGLE' ? 1 : 2;
            $maxGuests = $roomsCount * $perRoomCapacity;

            if ($guestsCount > $maxGuests) {
                $this->addFlash('error', 'Guests exceed room occupancy capacity.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            $prixParNuit = (float) ($reservation->getHebergement()?->getPrixParNuit() ?? 0);
            $pricing = $this->calculateTotal(
                $prixParNuit,
                $dateCheckin,
                $dateCheckout,
                $roomsCount,
                $roomType
            );

            $reservation
                ->setNomClient($nomClient)
                ->setEmailClient($emailClient !== '' ? $emailClient : null)
                ->setDateCheckin($dateCheckin)
                ->setDateCheckout($dateCheckout)
                ->setGuestsCount($guestsCount)
                ->setRoomsCount($roomsCount)
                ->setOccupancy($occupancy)
                ->setRoomType($roomType)
                ->setStatut($statut)
                ->setPrixTotal($pricing['total']);

            $entityManager->flush();

            $this->addFlash('success', 'Reservation updated successfully.');
            return $this->redirectToRoute('app_reservation_history');
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_reservation_'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Invalid delete request.');
            return $this->redirectToRoute('app_reservation_history');
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Reservation deleted successfully.');
        return $this->redirectToRoute('app_reservation_history');
    }

    private function normalizeRoomType(string $roomTypeRaw): string
    {
        $value = strtolower(trim($roomTypeRaw));

        return match ($value) {
            'deluxe' => 'Deluxe',
            'suite' => 'Suite',
            default => 'Standard',
        };
    }

    private function getRoomMultiplier(string $roomType): float
    {
        return match ($roomType) {
            'Deluxe' => 1.15,
            'Suite' => 1.30,
            default => 1.00,
        };
    }

    private function calculateTotal(
        float $prixParNuit,
        \DateTimeInterface $checkin,
        \DateTimeInterface $checkout,
        int $roomsCount,
        string $roomType
    ): array {
        $nights = max(1, (int) $checkin->diff($checkout)->days);
        $roomMultiplier = $this->getRoomMultiplier($roomType);

        $baseSubtotal = $prixParNuit * $nights * $roomsCount * $roomMultiplier;

        $weekendSurcharge = 0.0;
        $cursor = (clone $checkin);

        while ($cursor < $checkout) {
            $dayOfWeek = (int) $cursor->format('N');

            if ($dayOfWeek === 5 || $dayOfWeek === 6) {
                $weekendSurcharge += ($prixParNuit * $roomMultiplier * 0.15 * $roomsCount);
            }

            $cursor->modify('+1 day');
        }

        $discountRate = 0.0;
        if ($nights >= 10) {
            $discountRate = 0.20;
        } elseif ($nights >= 5) {
            $discountRate = 0.10;
        }

        $discountAmount = ($baseSubtotal + $weekendSurcharge) * $discountRate;
        $total = ($baseSubtotal + $weekendSurcharge) - $discountAmount;

        return [
            'nights' => $nights,
            'baseSubtotal' => round($baseSubtotal, 1),
            'weekendSurcharge' => round($weekendSurcharge, 1),
            'discountAmount' => round($discountAmount, 1),
            'total' => round($total, 1),
        ];
    }
}