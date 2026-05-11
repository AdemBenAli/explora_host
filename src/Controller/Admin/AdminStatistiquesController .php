<?php

namespace App\Controller\Admin;

use App\Repository\ActiviteRepository;
use App\Repository\AvisActiviteRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/statistiques', name: 'admin_stats_')]
class AdminStatistiquesController extends AbstractController
{
    public function __construct(
        private readonly ActiviteRepository    $activiteRepo,
        private readonly AvisActiviteRepository $avisRepo,
        private readonly UtilisateurRepository  $utilisateurRepo,
        private readonly VoyageRepository       $voyageRepo,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  Page principale : tableau de bord statistique complet
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('', name: 'index')]
    public function index(): Response
    {
        // ── Données brutes ────────────────────────────────────────────────────
        $activites = $this->activiteRepo->findAllOrdered();
        $agents    = $this->utilisateurRepo->findByRole('AGENT');

        $totalActivites  = count($activites);
        $totalAgents     = count($agents);
        $totalVoyages    = $this->voyageRepo->count([]);
        $totalAvis       = $this->avisRepo->count([]);
        $totalDisponibles = count(array_filter($activites, fn($a) => $a->isDisponible()));

        // ── Top 5 agents par nombre d'activités ───────────────────────────────
        $topAgentsActivites = $this->activiteRepo->findTopAgentsByActivityCount(5);

        // ── Top 5 agents par satisfaction (note moyenne de leurs activités) ───
        $topAgentsSatisfaction = $this->avisRepo->findTopAgentsBySatisfaction(5);

        // ── Top 6 activités les plus populaires (nb voyages associés) ─────────
        $topActivites = $this->activiteRepo->findTopActivitesByVoyageCount(6);

        // ── Répartition par catégorie ─────────────────────────────────────────
        $categorieStats = $this->activiteRepo->countByCategorie();
        // Calcul des pourcentages
        foreach ($categorieStats as &$cat) {
            $cat['pct'] = $totalActivites > 0
                ? round($cat['nb'] * 100 / $totalActivites, 1)
                : 0;
        }
        unset($cat);

        // ── Évolution mensuelle des activités (6 derniers mois) ───────────────
        $evolutionMensuelle = $this->activiteRepo->findMonthlyCreationStats(6);

        // ── Taux de disponibilité par catégorie ───────────────────────────────
        $dispoParCategorie = $this->activiteRepo->findAvailabilityRateByCategorie();

        // ── Avis récents ──────────────────────────────────────────────────────
        $avisRecents = $this->avisRepo->findRecent(5);

        return $this->render('admin/statistiques/index.html.twig', [
            // Totaux
            'totalActivites'   => $totalActivites,
            'totalAgents'      => $totalAgents,
            'totalVoyages'     => $totalVoyages,
            'totalAvis'        => $totalAvis,
            'totalDisponibles' => $totalDisponibles,

            // Classements
            'topAgentsActivites'   => $topAgentsActivites,
            'topAgentsSatisfaction' => $topAgentsSatisfaction,
            'topActivites'         => $topActivites,

            // Répartitions
            'categorieStats'    => $categorieStats,
            'dispoParCategorie' => $dispoParCategorie,

            // Séries temporelles
            'evolutionMensuelle' => $evolutionMensuelle,

            // Derniers avis
            'avisRecents' => $avisRecents,
        ]);
    }
}