<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * 🚦 SERVICE D'ANALYSE TRAFIC IA AVEC GEMINI 2.0 FLASH
 * Identique au TrafficAnalysisService.java
 */
class TrafficAnalysisService
{
    // ✅ VOTRE CLÉ GEMINI (Reversion à la clé valide AIza)
    // ✅ NOUVELLE CLÉ GEMINI FOURNIE PAR LE USER
    private const GEMINI_KEY = "AIzaSyA19cAuHxsu9WKZGeLqxLFfpQXZ-Kk1vw0";

    // ✅ MODÈLE GEMINI FLASH LATEST
    private const GEMINI_MODEL = "gemini-flash-latest";

    private $logger;
    private static $resultCache = []; // Cache pour assurer la cohérence (Même photo = Même résultat)

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 🎯 ANALYSER UNE IMAGE DE TRAFIC
     */
    public function analyserImage(string $imagePath): array
    {
        $this->logger->info("🚦 Début analyse image : " . basename($imagePath));

        if (!file_exists($imagePath)) {
            throw new \RuntimeException("❌ Image introuvable : " . $imagePath);
        }

        // 1️⃣ Lire et encoder l'image en Base64
        $fileContent = file_get_contents($imagePath);
        $imageHash = md5($fileContent); // HASH UNIQUE DE LA PHOTO

        // 2️⃣ Vérifier si on a déjà analysé CETTE EXACTE PHOTO (Cohérence demandée par le USER)
        if (isset(self::$resultCache[$imageHash])) {
            $this->logger->info("♻️ Résultat récupéré du cache pour cohérence (Hash: $imageHash)");
            return self::$resultCache[$imageHash];
        }

        $base64Image = base64_encode($fileContent);

        $this->logger->info("✅ Image encodée : " . strlen($fileContent) . " octets");

        // 2️⃣ Déterminer le type MIME
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $this->logger->info("📷 Type MIME : " . $mimeType);

        // 3️⃣ Construire l'URL de l'API Gemini (Utilisation de v1beta pour support 2.0)
        $endpoint = sprintf(
            "https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s",
            self::GEMINI_MODEL,
            self::GEMINI_KEY
        );

        $this->logger->info("🌐 Endpoint Gemini sollicité");

        // 4️⃣ Construire le prompt ultra-simple
        $prompt = "Regarde cette image de route.\n\n" .
                  "Réponds UNIQUEMENT avec UN SEUL MOT parmi :\n" .
                  "FLUIDE\n" .
                  "MODERE\n" .
                  "DENSE\n" .
                  "EMBOUTEILLAGE\n\n" .
                  "Réponds juste le mot, rien d'autre.";

        // 5️⃣ Construire le JSON de la requête
        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->logger->info("📤 Envoi de la requête à Gemini...");

        // 6️⃣ Appeler l'API Gemini via cURL (plus robuste que stream context sur Windows)
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix SSL Windows
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($responseBody === false) {
            throw new \RuntimeException("Impossible de contacter l'API Gemini : $curlError");
        }

        $jsonResponse = json_decode($responseBody, true);

        // 7️⃣ Parser la réponse
        if (isset($jsonResponse['error'])) {
            $errMsg = $jsonResponse['error']['message'] ?? 'Erreur inconnue de l\'API Gemini';
            $this->logger->error("❌ Erreur API Gemini : " . $responseBody);
            throw new \RuntimeException($errMsg);
        }

        if (!isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])) {
            $this->logger->error("❌ Réponse Inattendue Gemini : " . $responseBody);
            throw new \RuntimeException("L'IA n'a pas pu identifier le trafic sur cette image.");
        }

        $resultText = trim(strtoupper($jsonResponse['candidates'][0]['content']['parts'][0]['text']));
        $this->logger->info("🤖 Gemini répond : '{$resultText}'");

        // 8️⃣ Déterminer l'état
        $etat = $this->parseEtatTrafic($resultText);

        $result = [
            'etat' => $etat,
            'label' => $this->getEtatLabel($etat),
            'icone' => $this->getEtatIcone($etat),
            'scoreConfiance' => 90.0,
            'justification' => sprintf(
                "Analyse Gemini : État détecté = %s (IA Gemini 1.5 Flash)",
                $this->getEtatLabel($etat)
            )
        ];

        // Mettre en cache pour la cohérence
        if (isset($imageHash)) {
            self::$resultCache[$imageHash] = $result;
        }

        return $result;
    }

    private function parseEtatTrafic(string $text): string
    {
        if (str_contains($text, 'EMBOUTEILLAGE')) return 'EMBOUTEILLAGE';
        if (str_contains($text, 'DENSE')) return 'DENSE';
        if (str_contains($text, 'MODERE') || str_contains($text, 'MODÉRÉ')) return 'MODERE';
        if (str_contains($text, 'FLUIDE')) return 'FLUIDE';

        return 'MODERE';
    }

    private function getEtatLabel(string $etat): string
    {
        return match ($etat) {
            'EMBOUTEILLAGE' => 'Embouteillage',
            'DENSE' => 'Dense',
            'MODERE' => 'Modéré',
            'FLUIDE' => 'Fluide',
            default => 'Inconnu'
        };
    }

    private function getEtatIcone(string $etat): string
    {
        return match ($etat) {
            'EMBOUTEILLAGE' => '🔴',
            'DENSE' => '🟠',
            'MODERE' => '🟡',
            'FLUIDE' => '🟢',
            default => '⚪'
        };
    }
}
