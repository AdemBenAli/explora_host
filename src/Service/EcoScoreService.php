<?php

namespace App\Service;

use App\Entity\Transport;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * 🌱 SERVICE SCORE ÉCOLOGIQUE
 * Système de points pour récompenser les choix éco-responsables
 * 100 points = -20% sur le prochain voyage
 */
class EcoScoreService
{
    // 🎯 BARÈMES DE POINTS (Réajustés selon demande)
    private const POINTS_EXCELLENT = 20;  // Transport écologique
    private const POINTS_BON = 10;
    private const POINTS_MOYEN = 5;
    private const POINTS_VELO = 50;       // Réservation vélo

    private const SEUIL_REDUCTION = 1000; // 1000 points pour -20%
    private const TAUX_REDUCTION = 0.20;  // 20%

    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * 🎯 CRÉER TABLE SCORES SI N'EXISTE PAS
     */
    public function creerTableSiNonExistante(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS eco_scores (
                user_id INT PRIMARY KEY,
                points_actuels INT DEFAULT 0,
                points_total INT DEFAULT 0,
                niveau_actuel INT DEFAULT 0,
                reduction_disponible BOOLEAN DEFAULT FALSE,
                voyages_eco INT DEFAULT 0,
                co2_economise DOUBLE DEFAULT 0,
                derniere_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";

        try {
            $this->connection->executeStatement($sql);
            $this->logger->info("✅ Table eco_scores créée/vérifiée");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création table eco_scores : " . $e->getMessage());
        }
    }

    /**
     * 🌱 CALCULER POINTS POUR UN TRANSPORT
     */
    public function calculerPoints(Transport $transport): int
    {
        $categorie = $transport->getCategorieEcologique();

        if ($categorie === null) {
            return 0;
        }

        return match ($categorie) {
            'EXCELLENT' => self::POINTS_EXCELLENT,
            'BON'       => self::POINTS_BON,
            'MOYEN'     => self::POINTS_MOYEN,
            default     => 0,
        };
    }

    /**
     * 🚴 POINTS POUR RÉSERVATION VÉLO
     */
    public function getPointsVelo(): int
    {
        return self::POINTS_VELO;
    }

    /**
     * ➕ AJOUTER POINTS APRÈS RÉSERVATION
     */
    public function ajouterPoints(int $userId, Transport $transport, bool $estVelo): array
    {
        $this->creerTableSiNonExistante();

        $pointsGagnes = $estVelo ? self::POINTS_VELO : $this->calculerPoints($transport);

        if ($pointsGagnes === 0) {
            $this->logger->info("⚠️ Aucun point gagné pour ce transport");
            return $this->getScore($userId);
        }

        $co2Economise = $transport->getEmissionsKgCO2() !== null ? (float) $transport->getEmissionsKgCO2() : 0.0;

        // On utilise d'abord un SELECT pour avoir les valeurs actuelles et garantir la cohérence
        $scoreActuel = $this->getScore($userId);
        
        $nouveauxPoints = $scoreActuel['pointsActuels'] + $pointsGagnes;
        $nouveauTotal = $scoreActuel['pointsTotal'] + $pointsGagnes;
        $nouvelleReduction = ($nouveauxPoints >= self::SEUIL_REDUCTION) ? true : $scoreActuel['reductionDisponible'];
        $nouveauNiveau = (int) floor($nouveauTotal / self::SEUIL_REDUCTION);
        $nouveauVoyages = $scoreActuel['voyagesEco'] + 1;
        $nouveauCo2 = $scoreActuel['co2Economise'] + $co2Economise;

        $sql = "
            INSERT INTO eco_scores (user_id, points_actuels, points_total, voyages_eco, co2_economise, reduction_disponible, niveau_actuel, derniere_maj)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                points_actuels = ?,
                points_total = ?,
                voyages_eco = ?,
                co2_economise = ?,
                reduction_disponible = ?,
                niveau_actuel = ?,
                derniere_maj = CURRENT_TIMESTAMP
        ";

        try {
            $this->connection->executeStatement($sql, [
                $userId, $nouveauxPoints, $nouveauTotal, $nouveauVoyages, $nouveauCo2, $nouvelleReduction, $nouveauNiveau,
                $nouveauxPoints, $nouveauTotal, $nouveauVoyages, $nouveauCo2, $nouvelleReduction, $nouveauNiveau
            ]);

            $this->logger->info("✅ +{$pointsGagnes} points pour user #{$userId}");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur ajout points : " . $e->getMessage());
        }

        return $this->getScore($userId);
    }

    /**
     * ❌ RETIRER POINTS LORS D'UNE ANNULATION
     */
    public function retirerPoints(int $userId, Transport $transport): array
    {
        $pointsAPerdre = $this->calculerPoints($transport);

        if ($pointsAPerdre === 0) {
            return $this->getScore($userId);
        }

        $co2Perdu = $transport->getEmissionsKgCO2() !== null ? (float) $transport->getEmissionsKgCO2() : 0.0;

        $scoreActuel = $this->getScore($userId);
        
        $nouveauxPoints = max(0, $scoreActuel['pointsActuels'] - $pointsAPerdre);
        $nouveauTotal   = max(0, $scoreActuel['pointsTotal'] - $pointsAPerdre);
        $nouvelleReduction = ($nouveauxPoints >= self::SEUIL_REDUCTION);
        $nouveauNiveau  = (int) floor($nouveauTotal / self::SEUIL_REDUCTION);
        $nouveauVoyages = max(0, $scoreActuel['voyagesEco'] - 1);
        $nouveauCo2     = max(0.0, $scoreActuel['co2Economise'] - $co2Perdu);

        $sql = "
            UPDATE eco_scores SET
                points_actuels = ?,
                points_total = ?,
                voyages_eco = ?,
                co2_economise = ?,
                reduction_disponible = ?,
                niveau_actuel = ?,
                derniere_maj = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ";

        try {
            $this->connection->executeStatement($sql, [
                $nouveauxPoints, $nouveauTotal, $nouveauVoyages, $nouveauCo2, (int)$nouvelleReduction, $nouveauNiveau, $userId
            ]);
            $this->logger->info("❌ -{$pointsAPerdre} points pour user #{$userId} (annulation)");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur retrait points : " . $e->getMessage());
        }

        return $this->getScore($userId);
    }

    /**
     * 📊 RÉCUPÉRER SCORE UTILISATEUR
     */
    public function getScore(int $userId): array
    {
        $sql = "SELECT * FROM eco_scores WHERE user_id = ?";

        try {
            $data = $this->connection->executeQuery($sql, [$userId])->fetchAssociative();

            if ($data) {
                return $this->parseScoreData($data);
            }
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur récupération score : " . $e->getMessage());
        }

        // Créer nouveau score par défaut
        return [
            'userId' => $userId,
            'pointsActuels' => 0,
            'pointsTotal' => 0,
            'niveauActuel' => 0,
            'reductionDisponible' => false,
            'voyagesEco' => 0,
            'co2Economise' => 0.0,
            'progressionPourcent' => 0,
            'badgeNiveau' => '🌍 Nouveau',
            'pointsRestants' => self::SEUIL_REDUCTION
        ];
    }

    private function parseScoreData(array $data): array
    {
        $pointsActuels = (int) $data['points_actuels'];
        $niveauActuel = (int) $data['niveau_actuel'];
        
        // Calcul dynamique pour éviter les incohérences si le seuil change
        $reductionDisponible = $pointsActuels >= self::SEUIL_REDUCTION;

        // Calculs annexes
        $progressionPourcent = min(100, intval(($pointsActuels * 100) / self::SEUIL_REDUCTION));
        $pointsRestants = max(0, self::SEUIL_REDUCTION - $pointsActuels);

        $badgeNiveau = '🌍 Nouveau';
        if ($niveauActuel >= 10) $badgeNiveau = '🏆 Légende Verte';
        elseif ($niveauActuel >= 5) $badgeNiveau = '💚 Expert Éco';
        elseif ($niveauActuel >= 3) $badgeNiveau = '🌱 Éco-Warrior';
        elseif ($niveauActuel >= 1) $badgeNiveau = '🌿 Débutant Vert';

        return [
            'userId' => (int) $data['user_id'],
            'pointsActuels' => $pointsActuels,
            'pointsTotal' => (int) $data['points_total'],
            'niveauActuel' => $niveauActuel,
            'reductionDisponible' => $reductionDisponible,
            'voyagesEco' => (int) $data['voyages_eco'],
            'co2Economise' => (float) $data['co2_economise'],
            'derniereMaj' => $data['derniere_maj'],
            'progressionPourcent' => $progressionPourcent,
            'badgeNiveau' => $badgeNiveau,
            'pointsRestants' => $pointsRestants
        ];
    }

    /**
     * 💰 APPLIQUER RÉDUCTION SI DISPONIBLE
     */
    public function appliquerReduction(int $userId, float $prixOriginal): float
    {
        $score = $this->getScore($userId);

        if (!$score['reductionDisponible']) {
            return $prixOriginal;
        }

        $reduction = $prixOriginal * self::TAUX_REDUCTION;
        $prixReduit = $prixOriginal - $reduction;

        $this->logger->info(sprintf("💰 Réduction -20%% appliquée pour user #%d: %.2f DT → %.2f DT",
            $userId, $prixOriginal, $prixReduit));

        return $prixReduit;
    }

    /**
     * ✅ CONSOMMER RÉDUCTION APRÈS PAIEMENT
     */
    public function consommerReduction(int $userId): void
    {
        $sql = "
            UPDATE eco_scores 
            SET points_actuels = points_actuels - ?,
                reduction_disponible = FALSE,
                niveau_actuel = niveau_actuel + 1,
                derniere_maj = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ";

        try {
            $this->connection->executeStatement($sql, [self::SEUIL_REDUCTION, $userId]);
            $this->logger->info("✅ Réduction consommée pour user #{$userId}, -100 pts");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur consommation réduction : " . $e->getMessage());
        }
    }
}
