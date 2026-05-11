<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Enum\StatutBillet;
use App\Enum\TypeTransport;
use App\Repository\BilletRepository;
use App\Service\DynamicPricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/billets')]
class AdminBilletController extends AbstractController
{
    public function __construct(
        private BilletRepository       $billetRepository,
        private EntityManagerInterface $em,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private DynamicPricingService $dynamicPricingService
    ) {}

    #[Route('', name: 'admin_billet_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statut = $request->query->get('statut', 'tous');
        $search = $request->query->get('search', '');
        $sort   = $request->query->get('sort', 'date_desc');

        $billets = $this->getBilletsFiltres($statut, $search, $sort);

        $stats = [
            'en_attente' => $this->billetRepository->countByStatut(StatutBillet::EN_ATTENTE),
            'confirme'   => $this->billetRepository->countByStatut(StatutBillet::CONFIRME),
            'annule'     => $this->billetRepository->countByStatut(StatutBillet::ANNULE),
            'revenu'     => $this->billetRepository->countByStatut(StatutBillet::CONFIRME),
        ];

        return $this->render('admin_billet/index.html.twig', [
            'billets'       => $billets,
            'stats'         => $stats,
            'currentStatut' => $statut,
            'search'        => $search,
            'sort'          => $sort,
        ]);
    }

    #[Route('/{id}', name: 'admin_billet_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Billet $billet): Response
    {
        return $this->render('admin_billet/show.html.twig', [
            'billet' => $billet,
        ]);
    }

    #[Route('/{id}/csrf-token', name: 'admin_billet_csrf_token', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function csrfToken(int $id): Response
    {
        $token = $this->csrfTokenManager->getToken('changer_statut_' . $id)->getValue();
        return new Response($token);
    }

    #[Route('/{id}/changer-statut', name: 'admin_billet_changer_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changerStatut(Request $request, Billet $billet): Response
    {
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('changer_statut_' . $billet->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_billet_index');
        }

        $nouveauStatutStr = $request->request->get('statut');

        try {
            $nouveauStatut = StatutBillet::from($nouveauStatutStr);
            if ($nouveauStatut === StatutBillet::PAYE) {
                $this->addFlash('error', 'Le statut Paye est desactive.');
                return $this->redirectToRoute('admin_billet_index');
            }

            $ancienStatut = $billet->getStatut();
            $transport = $billet->getTransport();

            if ($transport) {
                $seatsPerBillet = in_array($transport->getType(), [TypeTransport::TAXI, TypeTransport::VOITURE], true)
                    ? 4
                    : $billet->getNombrePlaces();

                if ($ancienStatut !== StatutBillet::ANNULE && $nouveauStatut === StatutBillet::ANNULE) {
                    $transport->libererPlaces($seatsPerBillet);
                }

                if ($ancienStatut === StatutBillet::ANNULE && $nouveauStatut !== StatutBillet::ANNULE) {
                    if ($transport->getPlacesDisponibles() < $seatsPerBillet) {
                        $this->addFlash('error', 'Places insuffisantes.');
                        return $this->redirectToRoute('admin_billet_index');
                    }
                    $transport->reserverPlaces($seatsPerBillet);
                }
                
                $this->dynamicPricingService->loadPopularityMap();
                $this->dynamicPricingService->updatePriceIfNeeded($transport);
            }

            $billet->setStatut($nouveauStatut);
            $this->em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('admin_billet_index');
    }

    #[Route('/{id}/supprimer', name: 'admin_billet_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Billet $billet): Response
    {
        if (!$this->isCsrfTokenValid('delete_billet_' . $billet->getId(), $request->request->get('_token', ''))) {
            return $this->redirectToRoute('admin_billet_index');
        }

        $transport = $billet->getTransport();
        if ($billet->getStatut() !== StatutBillet::ANNULE && $transport) {
            $seats = in_array($transport->getType(), [TypeTransport::TAXI, TypeTransport::VOITURE], true) ? 4 : $billet->getNombrePlaces();
            $transport->libererPlaces($seats);
            $this->dynamicPricingService->loadPopularityMap();
            $this->dynamicPricingService->updatePriceIfNeeded($transport);
        }

        $this->em->remove($billet);
        $this->em->flush();
        return $this->redirectToRoute('admin_billet_index');
    }

    private function getBilletsFiltres(string $statut, string $search, string $sort): array
    {
        $qb = $this->billetRepository->createQueryBuilder('b')->leftJoin('b.transport', 't')->addSelect('t');
        if ($statut !== 'tous' && $s = StatutBillet::tryFrom($statut)) {
            $qb->andWhere('b.statut = :s')->setParameter('s', $s);
        }
        if ($search) {
            $qb->andWhere('LOWER(t.origine) LIKE :q OR LOWER(t.destination) LIKE :q')->setParameter('q', '%'.$search.'%');
        }
        match($sort) {
            'date_asc' => $qb->orderBy('b.dateReservation', 'ASC'),
            default => $qb->orderBy('b.dateReservation', 'DESC'),
        };
        return $qb->getQuery()->getResult();
    }

    /* ══════════════════════════════════════════════════════
       ANALYSER — Analyse IA des Réservations via Gemini
    ══════════════════════════════════════════════════════ */
    #[Route('/analyser', name: 'admin_billet_analyser', methods: ['POST'])]
    public function analyserIA(\App\Service\BilletAnalysisService $aiService): JsonResponse
    {
        try {
            $html = $aiService->analyzeBillets();
            return new JsonResponse(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function normalizeSearchTerm(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
