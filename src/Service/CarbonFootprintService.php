<?php

namespace App\Service;

use App\Entity\Transport;
use App\Enum\TypeTransport;

class CarbonFootprintService
{
    private const CO2_AVION_COURT = 258.0;
    private const CO2_AVION_MOYEN = 187.0;
    private const CO2_AVION_LONG = 152.0;
    private const CO2_TRAIN = 14.0;
    private const CO2_BUS = 68.0;
    private const CO2_VOITURE = 193.0;
    private const CO2_BATEAU = 250.0;
    private const CO2_TAXI = 193.0;

    private const COORDS = [
        "tunis" => [36.8065, 10.1815],
        "sfax" => [34.7406, 10.7603],
        "sousse" => [35.8256, 10.6369],
        "bizerte" => [37.2746, 9.8739],
        "gabes" => [33.8815, 10.0982],
        "gabès" => [33.8815, 10.0982],
        "kairouan" => [35.6781, 10.0963],
        "monastir" => [35.7778, 10.8263],
        "mahdia" => [35.5047, 11.0622],
        "nabeul" => [36.4516, 10.7354],
        "hammamet" => [36.4000, 10.6167],
        "la marsa" => [36.8781, 10.3250],
        "marsa" => [36.8781, 10.3250],
        "carthage" => [36.8531, 10.3231],
        "sidi bou said" => [36.8686, 10.3406],
        "ariana" => [36.8625, 10.1956],
        "la soukra" => [36.8664, 10.1108],
        "soukra" => [36.8664, 10.1108],
        "manouba" => [36.8097, 10.0968],
        "ben arous" => [36.7540, 10.2185],
        "ezzahra" => [36.7500, 10.3167],
        "hammam lif" => [36.7289, 10.3414],
        "rades" => [36.7697, 10.2753],
        "bardo" => [36.8108, 10.1381],
        "paris" => [48.8566, 2.3522],
        "marseille" => [43.2965, 5.3698],
        "lyon" => [45.7640, 4.8357],
        "nice" => [43.7102, 7.2620],
        "toulouse" => [43.6047, 1.4442],
        "bordeaux" => [44.8378, -0.5792],
        "lille" => [50.6292, 3.0573],
        "nantes" => [47.2184, -1.5536],
        "strasbourg" => [48.5734, 7.7521],
        "montpellier" => [43.6108, 3.8767],
        "rome" => [41.9028, 12.4964],
        "madrid" => [40.4168, -3.7038],
        "londres" => [51.5074, -0.1278],
        "london" => [51.5074, -0.1278],
        "berlin" => [52.5200, 13.4050],
        "istanbul" => [41.0082, 28.9784],
        "barcelone" => [41.3851, 2.1734],
        "amsterdam" => [52.3676, 4.9041],
        "bruxelles" => [50.8503, 4.3517],
        "vienne" => [48.2082, 16.3738],
        "prague" => [50.0755, 14.4378],
        "dubai" => [25.2048, 55.2708],
        "new york" => [40.7128, -74.0060],
        "tokyo" => [35.6762, 139.6503],
        "singapour" => [1.3521, 103.8198],
        "sydney" => [-33.8688, 151.2093],
    ];

    public function calculerEmpreinte(Transport $transport): array
    {
        $distanceKm = $this->obtenirDistanceReelle($transport->getOrigine(), $transport->getDestination());
        $coefficientCO2 = $this->obtenirCoefficientCO2($transport->getType(), $distanceKm);
        $emissionsKg = ($distanceKm * $coefficientCO2) / 1000.0;
        $categorie = $this->determinerCategorie($transport->getType(), $emissionsKg);
        $explication = $this->genererExplication($transport->getType(), $distanceKm, $emissionsKg);

        return [
            'distanceKm' => $distanceKm,
            'emissionsKgCO2' => $emissionsKg,
            'coefficientCO2' => $coefficientCO2,
            'categorie' => $categorie,
            'explication' => $explication
        ];
    }

    public function getDistance(string $origine, string $destination): float
    {
        return $this->obtenirDistanceReelle($origine, $destination);
    }

    private function obtenirDistanceReelle(string $origine, string $destination): float
    {
        return $this->calculerDistanceHaversine($origine, $destination);
    }

    private function calculerDistanceHaversine(string $origine, string $destination): float
    {
        $coord1 = $this->getCoordinates($origine);
        $coord2 = $this->getCoordinates($destination);

        if ($coord1 === null || $coord2 === null) {
            return 500.0;
        }

        $lat1 = deg2rad($coord1[0]);
        $lon1 = deg2rad($coord1[1]);
        $lat2 = deg2rad($coord2[0]);
        $lon2 = deg2rad($coord2[1]);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = 6371 * $c;

        return $distanceKm;
    }

    private function getCoordinates(string $ville): ?array
    {
        $villeNorm = mb_strtolower(trim($ville), 'UTF-8');
        $villeNorm = str_replace(['à','á','â','ã','ä','å'], 'a', $villeNorm);
        $villeNorm = str_replace(['è','é','ê','ë'], 'e', $villeNorm);
        $villeNorm = str_replace(['ç'], 'c', $villeNorm);

        return self::COORDS[$villeNorm] ?? null;
    }

    private function obtenirCoefficientCO2(TypeTransport $type, float $distanceKm): float
    {
        switch ($type->value) {
            case TypeTransport::AVION->value:
                if ($distanceKm < 1000) return self::CO2_AVION_COURT;
                if ($distanceKm < 3500) return self::CO2_AVION_MOYEN;
                return self::CO2_AVION_LONG;
            case TypeTransport::TRAIN->value:
                return self::CO2_TRAIN;
            case TypeTransport::BUS->value:
                return self::CO2_BUS;
            case TypeTransport::VOITURE->value:
                return self::CO2_VOITURE;
            case TypeTransport::TAXI->value:
                return self::CO2_TAXI;
            case TypeTransport::BATEAU->value:
                return self::CO2_BATEAU;
            default:
                return 0;
        }
    }

    private function determinerCategorie(TypeTransport $type, float $emissionsKg): string
    {
        if ($type->value === TypeTransport::TRAIN->value) return 'EXCELLENT';
        if ($emissionsKg < 10) return 'EXCELLENT';
        if ($emissionsKg < 50) return 'BON';
        if ($emissionsKg < 150) return 'MOYEN';
        if ($emissionsKg < 300) return 'MAUVAIS';
        return 'TRES_MAUVAIS';
    }

    private function genererExplication(TypeTransport $type, float $distanceKm, float $emissionsKg): string
    {
        $arbresNecessaires = (int) ceil($emissionsKg / 25.0);
        $pluriel = $arbresNecessaires > 1 ? 's' : '';
        return sprintf(
            "%s pour %.0f km = %.1f kg CO2\n🌳 %d arbre%s pendant 1 an pour compenser",
            ucfirst(strtolower($type->value)), $distanceKm, $emissionsKg, $arbresNecessaires, $pluriel
        );
    }
}
