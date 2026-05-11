<?php

namespace App\Controller\Admin;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/agents', name: 'admin_agent_')]
class AdminAgentController extends AbstractController
{
    // ── Liste de tous les agents ───────────────────────────────────────────
    #[Route('', name: 'index')]
    public function index(
        Request               $request,
        UtilisateurRepository $userRepo
    ): Response {
        $search = $request->query->get('q', '');

        $agents = $userRepo->findAgents($search);   // méthode à créer ci-dessous

        return $this->render('/activite/admin/agents/listeAgents.html.twig', [
            'agents' => $agents,
            'search' => $search,
        ]);
    }

    // ── Activités d'un agent spécifique ───────────────────────────────────
    #[Route('/{id}/activites', name: 'activites')]
    public function activites(
        int                   $id,
        Request               $request,
        UtilisateurRepository $userRepo,
        ActiviteRepository    $activiteRepo
    ): Response {
        $agent = $userRepo->find($id);

        if (!$agent || $agent->getRole() !== 'AGENT') {
            $this->addFlash('error', 'Agent introuvable.');
            return $this->redirectToRoute('admin_agent_index');
        }

        $search        = $request->query->get('q', '');
        $categorie     = $request->query->get('categorie', '');
        $disponibilite = $request->query->get('disponibilite', 'toutes');

        $activites  = $activiteRepo->findByAgent(
            $agent->getId(), $search, $categorie, $disponibilite
        );
        $categories = $activiteRepo->findCategoriesByAgent($agent->getId());

        $stats = [
            'total'      => count($activites),
            'disponible' => count(array_filter($activites, fn(Activite $a) => $a->isDisponible())),
            'completes'  => count(array_filter($activites, fn(Activite $a) => !$a->isDisponible())),
        ];

        return $this->render('/activite/admin/agents/activitesAgents.html.twig', [
            'agent'         => $agent,
            'activites'     => $activites,
            'categories'    => $categories,
            'stats'         => $stats,
            'search'        => $search,
            'categorie'     => $categorie,
            'disponibilite' => $disponibilite,
        ]);
    }
}