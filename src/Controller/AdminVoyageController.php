<?php

namespace App\Controller;

use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/voyages', name: 'admin_voyage_')]
class AdminVoyageController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, VoyageRepository $voyageRepo): Response
    {
        $search = $request->query->get('search', '');
        
        if ($search !== '') {
            $voyages = $voyageRepo->createQueryBuilder('v')
                ->where('v.nom LIKE :search OR v.destination LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('v.dateDepart', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $voyages = $voyageRepo->findBy([], ['dateDepart' => 'DESC']);
        }

        return $this->render('voyage/admin_index.html.twig', [
            'voyages' => $voyages,
            'search' => $search,
        ]);
    }
}
