<?php

namespace App\Service;

use App\Entity\Transport;
use App\Enum\TypeTransport;
use App\Repository\BilletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * 💰 SERVICE DE TARIFICATION DYNAMIQUE PROFESSIONNELLE
 * Calcule automatiquement les prix selon la demande, l'offre et la temporalité.
 */
class DynamicPricingService
{
    private const SEUIL_VARIATION_MIN = 0.02; // 2% minimum pour déclencher une persistance

    // 🎯 CAPACITÉS MOYENNES (pour calcul du remplissage)
    private const CAPACITES = [
        'AVION'   => 180,
        'TRAIN'   => 400,
        'BUS'     => 50,
        'BATEAU'  => 500,
        'VOITURE' => 4,
        'TAXI'    => 4,
    ];

    // 🎯 VOLATILITÉ MAX (multiplicateur max autorisé)
    private const VOLATILITE = [
        'AVION'   => 2.5,
        'TRAIN'   => 1.8,
        'BUS'     => 1.5,
        'BATEAU'  => 2.2,
        'VOITURE' => 1.6,
        'TAXI'    => 2.0,
    ];

    private array $popularityMap = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly SaturationPredictionService $saturationService
    ) {}

    /**
     * 📊 PRÉ-CHARGER LA POPULARITÉ POUR ÉVITER LE N+1
     */
    public function loadPopularityMap(): void
    {
        $sql = "
            SELECT t.id, COUNT(b.id) as total_billets
            FROM transport t
            LEFT JOIN billet b ON t.id = b.transport_id
            GROUP BY t.id
            ORDER BY total_billets DESC
        ";

        $results = $this->em->getConnection()->fetchAllAssociative($sql);
        $rank = 1;
        foreach ($results as $row) {
            $this->popularityMap[$row['id']] = $rank++;
        }
    }

    /**
     * 💰 CALCULE LE PRIX DYNAMIQUE ACTUEL AVEC DÉTAILS
     */
    public function calculerPrix(Transport $transport): array
    {
        $prixInitial = (float) ($transport->getPrixOriginal() ?? $transport->getPrix());
        $mult = 1.0;
        $details = [];

        // ── 1️⃣ TEMPS AVANT DÉPART (Courbe de rareté temporelle) ──
        $now = new \DateTime();
        $diff = $now->diff($transport->getDateDepart());
        $joursAvant = max(0, (int) $diff->format('%r%a'));

        // Courbe ajustée : Atteint ~1.0x (prix normal) à J-7.
        // J-0: ~2.0x | J-7: ~1.1x | J-14: ~0.9x | J-30: ~0.82x (Prix plancher)
        $timeMult = 0.82 + (1.18 * exp(-$joursAvant / 5.0));
        $mult *= $timeMult;
        
        if ($joursAvant < 3) $details[] = "Dernière minute (+".round(($timeMult-1)*100)."%)";
        elseif ($joursAvant > 10) $details[] = "Réservation anticipée (Réduction " . abs(round(($timeMult-1)*100)) . "%)";

        // ── 2️⃣ SATURATION FUTURE PRÉVUE (Indicateur de rareté de l'offre) ──
        $prediction = $this->saturationService->predictSaturation($transport);
        $futureOccupation = ($prediction['score'] ?? 50) / 100.0;

        // Formule plus réactive : 0.9 (Prix plancher si vide) -> 2.2 (Max si 100% saturé)
        $satMult = 0.9 + (1.3 * $futureOccupation);
        $mult *= $satMult;

        $pctChangeSat = round(($satMult - 1) * 100);
        $details[] = "Demande prévue (" . ($pctChangeSat >= 0 ? "+" : "") . $pctChangeSat . "%)";

        // ── 3️⃣ POPULARITÉ ──
        $rang = $this->popularityMap[$transport->getId()] ?? 999;
        if ($rang === 1) { $mult *= 1.3; $details[] = "Top #1 Dest. (+30%)"; }
        elseif ($rang <= 3) { $mult *= 1.2; $details[] = "Top #3 Dest. (+20%)"; }
        elseif ($rang <= 10) { $mult *= 1.1; $details[] = "Populaire (+10%)"; }

        // ── 4️⃣ SAISONNALITÉ, WEEK-ENDS ET JOURS DE FÊTES ──
        $mois = (int) $transport->getDateDepart()->format('n');
        $jourSemaine = (int) $transport->getDateDepart()->format('N');
        $heure = (int) $transport->getHeureDepart()->format('G');
        $jourMois = $transport->getDateDepart()->format('d-m');

        // Liste des jours fériés majeurs (Exemples: Jour de l'an, fêtes d'indépendance/république, Noël)
        $joursFeries = ['01-01', '20-03', '09-04', '01-05', '25-07', '13-08', '15-10', '25-12'];

        if (in_array($mois, [6, 7, 8])) { 
            $mult *= 1.3; 
            $details[] = "Haute Saison (+30%)"; 
        }

        if (in_array($jourMois, $joursFeries)) {
            $mult *= 1.25; 
            $details[] = "Jour de fête (+25%)";
        } elseif (in_array($jourSemaine, [6, 7])) { 
            // Si c'est un week-end (et pas un jour de fête)
            $mult *= 1.20; 
            $details[] = "Week-end (+20%)"; 
        }

        // Les heures d'affluence génèrent plus de demande naturelle
        if (($heure >= 7 && $heure <= 9) || ($heure >= 17 && $heure <= 19)) {
            $mult *= 1.15; 
            $details[] = "Heure d'affluence (+15%)";
        }

        // ── 5️⃣ CAS SPÉCIAL : BRADERIE DE DERNIÈRE MINUTE ──
        // EXCEPTION : Les AVIONS ne bénéficient jamais de braderie (toujours chers en dernière minute)
        if ($transport->getType()->name !== 'AVION' && $joursAvant < 3 && $futureOccupation < 0.20) {
            $mult = 0.6; // Prix cassé à -40% par rapport à l'original
            $details = ["🔥 Vente Flash : Dernière minute & Peu rempli (-40%)"];
        }

        // ── LIMITE DE VOLATILITÉ ──
        $vMax = self::VOLATILITE[$transport->getType()->name] ?? 2.0;
        $mult = max(0.4, min($vMax, $mult)); // Plancher à 0.4x (60% de réduction max)

        return [
            'prix' => round($prixInitial * $mult, 2),
            'variation' => round(($mult - 1) * 100, 0),
            'details' => $details
        ];
    }

    /**
     * 💾 MET À JOUR ET PERSISTE SI NÉCESSAIRE
     */
    public function updatePriceIfNeeded(Transport $transport): void
    {
        // On s'assure d'avoir un prix de base immuable
        if ($transport->getPrixOriginal() === null || (float)$transport->getPrixOriginal() <= 0) {
            $transport->setPrixOriginal($transport->getPrix());
        }

        $derniereMaj = $transport->getDerniereMajPrix();
        $now = new \DateTime();
        
        if ($derniereMaj !== null) {
            $interval = $now->getTimestamp() - $derniereMaj->getTimestamp();
            if ($interval < 1) { // Cooldown réduit à 1s pour une réactivité totale en test
                return;
            }
        }

        $res = $this->calculerPrix($transport);
        $nouveauPrix = (string)$res['prix'];
        $prixActuel = $transport->getPrix();

        // Si le prix a changé ou si c'est la première fois, on met à jour.
        // On enlève le seuil de 2% pour garantir que le prix suit toujours la date/saturation.
        if ($nouveauPrix !== $prixActuel || $derniereMaj === null) {
            $transport->setPrix($nouveauPrix);
            $transport->setDerniereMajPrix($now);
            $this->em->persist($transport);
            // Pas de flush ici, le contrôleur s'en occupe ou Symfony à la fin de la requête
        }
    }
}
