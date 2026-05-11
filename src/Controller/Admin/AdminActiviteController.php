<?php

namespace App\Controller\Admin;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use App\Repository\AvisActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/activites', name: 'admin_activite_')]
class AdminActiviteController extends AbstractController
{
    // ── Liste globale des activités (toutes agents confondus) ─────────────
    #[Route('', name: 'index')]
    public function index(
        Request            $request,
        ActiviteRepository $activiteRepo
    ): Response {
        $activites  = $activiteRepo->findAllOrdered();
        $categories = $activiteRepo->findAllCategories();
        $villes     = array_unique(array_map(fn($a) => $a->getVille(), $activites));

        return $this->render('activite/admin/index.html.twig', [
            'activites'    => $activites,
            'categories'   => $categories,
            'nbCategories' => count($categories),
            'nbVilles'     => count($villes),
        ]);
    }

    // ── Détail d'une activité + ses avis ──────────────────────────────────
    #[Route('/{id}/detail', name: 'detail')]
    public function detail(
        int                    $id,
        EntityManagerInterface $em,
        AvisActiviteRepository         $avisRepo
        
    ): Response {
        $activite = $em->getRepository(Activite::class)->find($id);

        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée.');
        }

        $avis = $avisRepo->findBy(
            ['activite' => $activite],
            ['dateAvis' => 'DESC']
        );

        $moyenne = 0;
        if (count($avis) > 0) {
            $moyenne = array_sum(array_map(fn($a) => $a->getNote(), $avis)) / count($avis);
        }

        return $this->render('activite/admin/agents/activite/admin/activites/detail.html.twig', [
            'activite' => $activite,
            'avis'     => $avis,
            'nbAvis'   => count($avis),
            'moyenne'  => $moyenne,
        ]);
    }
}