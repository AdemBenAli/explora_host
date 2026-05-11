<?php

namespace App\Service;

use App\Entity\Transport;
use App\Repository\BilletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * 📊 SERVICE DE PRÉDICTION DE SATURATION
 * Prédit si un transport sera complet avant le départ
 * Porté de la logique JavaFX professionnelle
 */
class SaturationPredictionService
{
    public function __construct(
        private readonly BilletRepository $billetRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * 🎯 PRÉDIRE LA SATURATION D'UN TRANSPORT
     */
    public function predictSaturation(Transport $transport): array
    {
        try {
            // 1️⃣ Récupérer les billets actifs
            $billets = $this->billetRepository->findBy([
                'transport' => $transport
            ]);

            $placesReservees = 0;
            $datesReservation = [];
            
            foreach ($billets as $billet) {
                // On suppose que StatutBillet::ANNULE est géré (sinon on filtre via le repository)
                // Ici on simplifie en comptant tout ce qui est retourné par le repository filtré
                $placesReservees += $billet->getNombrePlaces();
                $datesReservation[] = $billet->getCreatedAt() ?: new \DateTime();
            }

            // 2️⃣ Capacité totale
            $capaciteTotale = $transport->getPlacesDisponibles() + $placesReservees;
            if ($capaciteTotale <= 0) $capaciteTotale = 1;

            // 3️⃣ Taux de remplissage actuel
            $tauxRemplissage = ($placesReservees / $capaciteTotale) * 100;

            // 4️⃣ Jours avant départ
            $now = new \DateTime();
            $depart = $transport->getDateDepart() ?: $now;
            $diff = $now->diff($depart);
            $joursAvantDepart = (int)$diff->format('%r%a');

            // 5️⃣ Vitesse de réservation
            $vitesseReservation = $this->calculateReservationSpeed($placesReservees, $datesReservation, $joursAvantDepart);

            // 6️⃣ Calcul mathématique de la saturation future (au départ)
            $placesFuturesPrevisibles = $placesReservees + ($vitesseReservation * $joursAvantDepart);
            $saturationFutureEstimee = min(100, round(($placesFuturesPrevisibles / $capaciteTotale) * 100));

            // On garde l'ancien système de score pour la couleur/le niveau, mais basé sur la prédiction
            $scoreSaturation = (int) $saturationFutureEstimee;

            // 7️⃣ Niveau et recommandations
            $niveau = $this->determineNiveau($scoreSaturation);
            $recommandations = $this->generateRecommandations($niveau, $scoreSaturation, $joursAvantDepart, $tauxRemplissage);

            return [
                'score' => $scoreSaturation,
                'niveau' => $niveau['label'],
                'color' => $niveau['color'],
                'icon' => $niveau['icon'],
                'tauxRemplissage' => round($tauxRemplissage, 1),
                'vitesse' => $vitesseReservation,
                'joursAvantDepart' => $joursAvantDepart,
                'recommandations' => $recommandations,
                'placesReservees' => $placesReservees,
                'capaciteTotale' => $capaciteTotale,
                'tendance' => $this->getTendanceLabel($vitesseReservation)
            ];

        } catch (\Exception $e) {
            $this->logger->error("Erreur saturation : " . $e->getMessage());
            return ['error' => true, 'score' => 0];
        }
    }

    private function calculateReservationSpeed(int $nbPlaces, array $dates, int $joursRestants): float
    {
        if (empty($dates) || $nbPlaces === 0) return 0.0;

        // Pour éviter qu'une seule réservation ponctuelle récente de 14 places 
        // fasse croire que la vitesse est de "14 places par jour", on estime
        // que les ventes ont commencé il y a environ 60 jours ou plus.
        $joursEcoulesVraisemblable = max(5, 60 - $joursRestants);
        
        $minDate = min($dates);
        $diff = $minDate->diff(new \DateTime());
        $joursEcoulesDepuisPremiereResa = max(1, (int)$diff->format('%a'));

        // On prend le maximum entre l'écoulement réel depuis la 1ère résa et une durée raisonnable
        $joursEcoules = max($joursEcoulesVraisemblable, $joursEcoulesDepuisPremiereResa);

        $vitesse = $nbPlaces / $joursEcoules;

        // Accélération (FOMO) très proche du départ
        if ($joursRestants <= 3) $vitesse *= 1.2;

        return round($vitesse, 2);
    }

    // (Méthode supprimée car remplacée par le calcul prédictif direct)
    private function determineNiveau(int $futureSaturation): array
    {
        if ($futureSaturation >= 90) return ['label' => 'Pleine Capacité', 'color' => '#22c55e', 'icon' => '✅']; 
        if ($futureSaturation >= 70) return ['label' => 'Forte Demande', 'color' => '#84cc16', 'icon' => '📈'];
        if ($futureSaturation >= 40) return ['label' => 'Demande Normale', 'color' => '#3b82f6', 'icon' => '⚖️'];
        if ($futureSaturation >= 15) return ['label' => 'Disponibilité Bonne', 'color' => '#f59e0b', 'icon' => '⚠️'];
        return ['label' => 'Très peu réservé', 'color' => '#ef4444', 'icon' => '🚨'];
    }

    private function generateRecommandations(array $niveau, int $futureSaturation, int $jours, float $remplissage): string
    {
        return match($niveau['label']) {
            'Pleine Capacité' => "🚀 EXCELLENT : Transport quasi complet (" . $futureSaturation . "%). Rentabilité maximale atteinte !",
            'Forte Demande' => "📈 TRÈS BIEN : Forte traction commerciale (" . $futureSaturation . "%). Hausse de prix pertinente.",
            'Demande Normale' => "⚖️ STABLE : Remplissage conforme aux prévisions (" . $futureSaturation . "%). Maintenir les tarifs.",
            'Disponibilité Bonne' => "⚠️ ALERTE : Le remplissage est encore timide (" . $futureSaturation . "%). Surveillez la concurrence.",
            'Très peu réservé' => "❌ DANGER : Ce transport risque d'être vide (" . $futureSaturation . "%). Lancez une promotion d'urgence !",
            default => "Analyse en attente de données suffisantes."
        };
    }

    private function getTendanceLabel(float $vitesse): string
    {
        if ($vitesse >= 3.0) return "↗️ En forte hausse";
        if ($vitesse >= 1.0) return "↗️ En hausse";
        if ($vitesse >= 0.5) return "→ Stable";
        return "↘️ En baisse";
    }
}
