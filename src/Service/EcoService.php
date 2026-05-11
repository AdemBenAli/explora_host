<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * 🌱 SERVICE ÉCO-RESPONSABLE INTELLIGENT
 * Gestion des recommandations vélo et codes promo uniques
 */
class EcoService
{
    private const DISTANCE_MAX_VELO = 10.0; // 10 KM MAX
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * 📏 CALCULER LA DISTANCE ENTRE 2 VILLES
     */
    public function calculerDistance(?string $ville1, ?string $ville2): float
    {
        if ($ville1 === null || $ville2 === null) {
            return -1.0;
        }

        $this->logger->info("📏 Calcul distance : {$ville1} → {$ville2}");

        $coords1 = $this->getCoordinates($ville1);
        $coords2 = $this->getCoordinates($ville2);

        if ($coords1 === null || $coords2 === null) {
            $this->logger->warning("⚠️ Coordonnées introuvables pour {$ville1} ou {$ville2}");
            return -1.0;
        }

        $distance = $this->haversineDistance($coords1[0], $coords1[1], $coords2[0], $coords2[1]);
        $this->logger->info(sprintf("✅ Distance calculée : %.1f km", $distance));

        return $distance;
    }

    /**
     * 🗺️ OBTENIR LES COORDONNÉES GPS D'UNE VILLE
     */
    private function getCoordinates(?string $ville): ?array
    {
        if ($ville === null) {
            return null;
        }

        $villeNorm = mb_strtolower(trim($ville), 'UTF-8');

        // TUNIS ET BANLIEUE
        if ($villeNorm === "tunis") return [36.8065, 10.1815];
        if ($villeNorm === "la marsa" || $villeNorm === "marsa") return [36.8781, 10.3250];
        if ($villeNorm === "carthage") return [36.8531, 10.3231];
        if ($villeNorm === "sidi bou said") return [36.8686, 10.3406];
        if ($villeNorm === "la soukra" || $villeNorm === "soukra") return [36.8664, 10.1108];
        if ($villeNorm === "ariana") return [36.8625, 10.1956];
        if ($villeNorm === "l'aouina" || $villeNorm === "aouina") return [36.8500, 10.2275];
        if ($villeNorm === "manouba") return [36.8097, 10.0968];
        if ($villeNorm === "ben arous") return [36.7540, 10.2185];
        if ($villeNorm === "ezzahra") return [36.7500, 10.3167];
        if ($villeNorm === "hammam lif") return [36.7289, 10.3414];
        if ($villeNorm === "rades") return [36.7697, 10.2753];
        if ($villeNorm === "bardo") return [36.8108, 10.1381];

        // AUTRES VILLES
        if ($villeNorm === "sfax") return [34.7406, 10.7603];
        if ($villeNorm === "sousse") return [35.8256, 10.6369];
        if ($villeNorm === "kairouan") return [35.6781, 10.0963];
        if ($villeNorm === "bizerte") return [37.2744, 9.8739];
        if ($villeNorm === "gabès" || $villeNorm === "gabes") return [33.8815, 10.0982];
        if ($villeNorm === "gafsa") return [34.425, 8.7842];
        if ($villeNorm === "monastir") return [35.7778, 10.8263];
        if ($villeNorm === "mahdia") return [35.5047, 11.0622];
        if ($villeNorm === "nabeul") return [36.4516, 10.7354];
        if ($villeNorm === "hammamet") return [36.4000, 10.6167];

        return null;
    }

    /**
     * 🌍 FORMULE DE HAVERSINE (distance GPS)
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }

    /**
     * ✅ VÉRIFIER SI VÉLO RECOMMANDÉ (< 10 KM)
     */
    public function isVeloRecommande(float $distance): bool
    {
        return $distance > 0 && $distance <= self::DISTANCE_MAX_VELO;
    }

    /**
     * 🏪 TROUVER LA BOUTIQUE DANS LA VILLE DE DÉPART
     */
    public function trouverBoutiqueDansVille(?string $villeDepart): ?array
    {
        if (empty(trim((string) $villeDepart))) {
            return null;
        }

        $this->logger->info("🔍 Recherche boutique dans {$villeDepart}");

        try {
            $sql = "SELECT * FROM boutique_velo WHERE LOWER(ville) = LOWER(?) LIMIT 1";
            $shop = $this->connection->executeQuery($sql, [trim($villeDepart)])->fetchAssociative();

            if ($shop) {
                $shop['distanceUtilisateur'] = 0.0;
                $this->logger->info("✅ Boutique trouvée à {$villeDepart} : " . $shop['nom']);
                return $shop;
            }

            $this->logger->info("⚠️ Pas de boutique à {$villeDepart}, recherche la plus proche...");
            return $this->trouverBoutiquePlusProche($villeDepart);

        } catch (\Exception $e) {
            // Silencing creation failure or return null
            $this->logger->error("❌ Erreur recherche boutique: " . $e->getMessage());
        }

        return null;
    }

    /**
     * 🔍 TROUVER LA BOUTIQUE LA PLUS PROCHE
     */
    private function trouverBoutiquePlusProche(string $ville): ?array
    {
        try {
            $sql = "SELECT * FROM boutique_velo";
            $shops = $this->connection->executeQuery($sql)->fetchAllAssociative();

            $coordsVille = $this->getCoordinates($ville);
            if ($coordsVille === null) {
                return null;
            }

            $plusProche = null;
            $distanceMin = PHP_FLOAT_MAX;

            foreach ($shops as $shop) {
                $distance = $this->haversineDistance(
                    $coordsVille[0], $coordsVille[1],
                    (float) $shop['latitude'], (float) $shop['longitude']
                );

                if ($distance < $distanceMin) {
                    $distanceMin = $distance;
                    $plusProche = $shop;
                    $plusProche['distanceUtilisateur'] = $distance;
                }
            }

            if ($plusProche !== null) {
                $this->logger->info(sprintf("✅ Boutique la plus proche : %s à %.1f km",
                    $plusProche['nom'], $plusProche['distanceUtilisateur']));
            }

            return $plusProche;

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur recherche boutique proche: " . $e->getMessage());
        }

        return null;
    }

    /**
     * 🎟️ GÉNÉRER UN CODE PROMO UNIQUE ET PERSISTANT
     */
    public function genererCodePromo(?int $userId, ?string $origine, ?string $destination): ?string
    {
        if ($userId === null || $origine === null || $destination === null) {
            return null;
        }

        $this->logger->info("🎟️ Génération code promo pour user #{$userId} : {$origine} → {$destination}");

        // 1️⃣ Vérifier si un code existe déjà
        $codeExistant = $this->getCodeExistant($userId, $origine, $destination);
        if ($codeExistant !== null) {
            $this->logger->info("♻️ Code promo existant : {$codeExistant}");
            return $codeExistant;
        }

        // 2️⃣ Générer un nouveau code unique
        $nouveauCode = $this->genererCodeUnique($userId, $origine, $destination);

        // 3️⃣ Sauvegarder en BDD
        $this->sauvegarderCodePromo($userId, $origine, $destination, $nouveauCode);

        $this->logger->info("✅ Nouveau code promo : {$nouveauCode}");
        return $nouveauCode;
    }

    /**
     * 🔍 VÉRIFIER SI CODE EXISTE DÉJÀ
     */
    private function getCodeExistant(int $userId, string $origine, string $destination): ?string
    {
        try {
            $sql = "SELECT code FROM code_promo_velo WHERE user_id = ? AND LOWER(origine) = LOWER(?) AND LOWER(destination) = LOWER(?)";
            $result = $this->connection->executeQuery($sql, [$userId, trim($origine), trim($destination)])->fetchOne();
            
            return $result !== false ? (string) $result : null;

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur vérification code existant: " . $e->getMessage());
        }

        return null;
    }

    /**
     * 🆕 GÉNÉRER UN CODE UNIQUE (MD5)
     */
    private function genererCodeUnique(int $userId, string $origine, string $destination): string
    {
        try {
            $input = $userId . ":" . mb_strtolower(trim($origine), 'UTF-8') . ":" . mb_strtolower(trim($destination), 'UTF-8');
            $hash = md5($input);
            $hashCode = strtoupper(substr($hash, 0, 8));
            return "ECO" . $hashCode;
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur génération hash: " . $e->getMessage());
            return "ECO" . strtoupper(substr(md5(uniqid()), 0, 8));
        }
    }

    /**
     * 💾 SAUVEGARDER LE CODE EN BDD
     */
    private function sauvegarderCodePromo(int $userId, string $origine, string $destination, string $code): void
    {
        try {
            // On crée la table au besoin (cas où elle n'existe pas en local)
            $this->connection->executeStatement("
                CREATE TABLE IF NOT EXISTS code_promo_velo (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    origine VARCHAR(255),
                    destination VARCHAR(255),
                    code VARCHAR(50),
                    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $sql = "INSERT INTO code_promo_velo (user_id, origine, destination, code, date_creation) VALUES (?, ?, ?, ?, NOW())";
            $this->connection->executeStatement($sql, [
                $userId,
                mb_strtolower(trim($origine), 'UTF-8'),
                mb_strtolower(trim($destination), 'UTF-8'),
                $code
            ]);

            $this->logger->info("💾 Code promo sauvegardé : {$code}");

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur sauvegarde code promo: " . $e->getMessage());
        }
    }

    /**
     * 🌱 CALCULER L'EMPREINTE CARBONE ÉVITÉE (en kg CO₂)
     */
    public function calculerCO2Evite(float $distance, ?string $typeTransport): float
    {
        if ($typeTransport === null) {
            return 0.0;
        }

        $type = mb_strtolower($typeTransport, 'UTF-8');
        switch ($type) {
            case "voiture": $emissionParKm = 120; break;
            case "taxi":    $emissionParKm = 150; break;
            case "bus":     $emissionParKm = 80; break;
            case "train":   $emissionParKm = 40; break;
            default:        $emissionParKm = 100; break;
        }

        $co2Evite = ($distance * $emissionParKm) / 1000.0; // Convertir en kg
        $this->logger->info(sprintf("🌱 CO₂ évité : %.2f kg pour %.1f km en %s", $co2Evite, $distance, $typeTransport));
        
        return $co2Evite;
    }
}
