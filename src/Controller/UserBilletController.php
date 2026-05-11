<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\Transport;
use App\Enum\StatutBillet;
use App\Enum\TypeTransport;
use App\Repository\BilletRepository;
use App\Repository\TransportRepository;
use App\Service\DynamicPricingService;
use App\Service\EcoScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserBilletController extends AbstractController
{
    private int $userId = 5;

    #[Route('/user/billets', name: 'user_billets')]
    public function index(Request $request, BilletRepository $billetRepository): Response
    {
        $statut    = $request->query->get('statut', 'tous');
        $recherche = trim((string) $request->query->get('recherche', ''));
        $sort      = $request->query->get('sort', 'date_desc');

        $qb = $billetRepository->createQueryBuilder('b')
            ->join('b.transport', 't')
            ->addSelect('t')
            ->where('b.userId = :uid')
            ->setParameter('uid', $this->userId);

        match ($statut) {
            'actifs'   => $qb->andWhere('b.statut != :s')->setParameter('s', StatutBillet::ANNULE),
            'annules'  => $qb->andWhere('b.statut = :s')->setParameter('s', StatutBillet::ANNULE),
            default    => null,
        };

        if ($recherche !== '') {
            $term = $this->normalizeSearchTerm($recherche);
            $orX = $qb->expr()->orX(
                'LOWER(t.compagnie) LIKE :q',
                'LOWER(t.origine) LIKE :q',
                'LOWER(t.destination) LIKE :q',
                'LOWER(t.type) LIKE :q',
                'LOWER(t.numeroVol) LIKE :q'
            );
            $qb->setParameter('q', '%' . $term . '%');
            $qb->andWhere($orX);
        }

        match ($sort) {
            'date_asc'   => $qb->orderBy('t.dateDepart', 'ASC'),
            'prix_asc'   => $qb->orderBy('b.prixTotal', 'ASC'),
            'prix_desc'  => $qb->orderBy('b.prixTotal', 'DESC'),
            default      => $qb->orderBy('b.dateReservation', 'DESC'), 
        };

        $billets = $qb->getQuery()->getResult();
        $allBillets  = $billetRepository->findBy(['userId' => $this->userId]);
        
        return $this->render('user_billet/index.html.twig', [
            'billets'       => $billets,
            'statut'        => $statut,
            'recherche'     => $recherche,
            'sort'          => $sort,
            'totalBillets'  => count($allBillets),
            'billetsActifs' => count(array_filter($allBillets, fn($b) => $b->getStatut() !== StatutBillet::ANNULE)),
            'totalDepense'  => array_reduce(array_filter($allBillets, fn($b) => $b->getStatut() !== StatutBillet::ANNULE), fn($carry, $b) => $carry + (float)$b->getPrixTotal(), 0.0),
        ]);
    }

    #[Route('/user/billets/{id}/modifier', name: 'user_billet_modifier', methods: ['POST'])]
    public function modifier(
        int $id,
        Request $request,
        BilletRepository $billetRepository,
        EntityManagerInterface $em,
        DynamicPricingService $dynamicPricingService
    ): Response {
        if (!$this->isCsrfTokenValid('modifier_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('user_billets');
        }

        $billet = $billetRepository->find($id);
        if (!$billet || $billet->getUserId() !== $this->userId) {
            $this->addFlash('error', 'Billet introuvable.');
            return $this->redirectToRoute('user_billets');
        }

        $nouvPlaces = (int) $request->request->get('nombrePlaces', 1);
        $transport = $billet->getTransport();
        $diff = $nouvPlaces - $billet->getNombrePlaces();

        if ($diff > 0 && $transport->getPlacesDisponibles() < $diff) {
            $this->addFlash('error', 'Places insuffisantes.');
            return $this->redirectToRoute('user_billets');
        }

        $transport->setPlacesDisponibles($transport->getPlacesDisponibles() - $diff);
        $billet->setNombrePlaces($nouvPlaces);
        $billet->calculerPrixTotal();

        $dynamicPricingService->loadPopularityMap();
        $dynamicPricingService->updatePriceIfNeeded($transport);
        
        $em->flush();
        $this->addFlash('success', 'Billet modifié.');
        return $this->redirectToRoute('user_billets');
    }

    #[Route('/user/billets/{id}/supprimer', name: 'user_billet_supprimer', methods: ['POST'])]
    public function supprimer(
        int $id,
        Request $request,
        BilletRepository $billetRepository,
        EntityManagerInterface $em,
        EcoScoreService $ecoScoreService,
        DynamicPricingService $dynamicPricingService
    ): Response {
        if (!$this->isCsrfTokenValid('supprimer_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('user_billets');
        }

        $billet = $billetRepository->find($id);
        if (!$billet) return $this->redirectToRoute('user_billets');

        $transport = $billet->getTransport();
        $ecoScoreService->retirerPoints($this->userId, $transport);
        $transport->libererPlaces($billet->getNombrePlaces());

        $em->remove($billet);
        $dynamicPricingService->loadPopularityMap();
        $dynamicPricingService->updatePriceIfNeeded($transport);
        
        $em->flush();
        $this->addFlash('success', 'Billet supprimé.');
        return $this->redirectToRoute('user_billets');
    }



    private function normalizeSearchTerm(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
