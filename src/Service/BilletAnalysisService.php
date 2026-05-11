<?php

namespace App\Service;

use App\Repository\BilletRepository;
use Psr\Log\LoggerInterface;

class BilletAnalysisService
{
    private const GEMINI_KEY = "AIzaSyA19cAuHxsu9WKZGeLqxLFfpQXZ-Kk1vw0";
    private const GEMINI_MODEL = "gemini-flash-latest";

    public function __construct(
        private BilletRepository $billetRepository,
        private LoggerInterface $logger
    ) {
    }

    public function analyzeBillets(): string
    {
        // 1️⃣ Fetch all tickets
        $billets = $this->billetRepository->findAll();
        
        // 2️⃣ Prepare data for AI (minimize size to save tokens)
        $dataToAnalyze = [];
        foreach ($billets as $b) {
            $t = $b->getTransport();
            $dataToAnalyze[] = [
                'id' => $b->getId(),
                'user' => 'User#' . $b->getUserId(),
                'places' => $b->getNombrePlaces(),
                'statut' => $b->getStatut()->name,
                'prix' => $b->getPrixTotal(),
                'dateResa' => $b->getDateReservation() ? $b->getDateReservation()->format('Y-m-d H:i') : '',
                'transport' => $t ? $t->getType()->name . ' (ID:' . $t->getId() . ')' : 'Inconnu',
            ];
        }

        // Limit to last 500 to avoid exceeding payload limits while still providing good data
        if (count($dataToAnalyze) > 500) {
            $dataToAnalyze = array_slice($dataToAnalyze, -500);
        }

        $jsonPayload = json_encode($dataToAnalyze);

        // 3️⃣ Construct the Prompt
        $prompt = "
Tu es un expert data scientist pour 'Explora', une plateforme de réservation.
Voici les données récentes des réservations au format JSON :
$jsonPayload

Génère une analyse stricte, professionnelle et concise basée UNIQUEMENT sur ces données.
S'il n'y a pas d'anomalies, renvoie simplement dans le tableau: 'Aucune anomalie majeure'.
NE FAIS PAS D'HALLUCINATIONS.

Réponds DIRECTEMENT et UNIQUEMENT avec un objet JSON ayant exactement cette structure :
{
  \"anomalies\": [\"string\", \"string\"],
  \"tendances\": [\"string\", \"string\"],
  \"recommandations\": [\"string\", \"string\"],
  \"predictions\": [\"string\", \"string\"]
}
";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . self::GEMINI_MODEL . ":generateContent?key=" . self::GEMINI_KEY;

        $data = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.2,
                "maxOutputTokens" => 2000,
                "responseMimeType" => "application/json"
            ]
        ];

        $payload = json_encode($data);

        // 3️⃣ Envoi via cURL (plus robuste que file_get_contents sur Windows/XAMPP)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix pour Windows/XAMPP SSL
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                return "<p style='color:red;'><i class='fas fa-wifi-slash'></i> Erreur de connexion au serveur IA : $curlError</p>";
            }

            if ($httpCode === 429) {
                return "
                <div style='text-align:center; padding:20px;'>
                    <i class='fas fa-clock' style='font-size:40px; color:#f39c12; margin-bottom:15px;'></i>
                    <h3 style='color:#navy;'>Limite de Quota Atteinte</h3>
                    <p style='color:#666;'>Vous avez fait trop de demandes en peu de temps. <br>Veuillez attendre 1 minute avant de cliquer à nouveau.</p>
                </div>";
            }

            if ($httpCode !== 200) {
                return "<p style='color:red;'><i class='fas fa-exclamation-circle'></i> L'API Google a renvoyé une erreur ($httpCode). Vérifiez votre connexion ou la clé API.</p>";
            }

            $jsonResponse = json_decode($response, true);
            if (isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
                $analysisData = json_decode($rawText, true);

                if (!$analysisData) {
                    return "<p style='color:orange;'><i class='fas fa-code'></i> Erreur de lecture des données IA. Réessayez.</p>";
                }

                $html = "<div style='text-align: left; padding: 15px;'>";
                
                $html .= "<h3 style='color: #e74c3c; margin-bottom:12px; font-size: 16px;'><i class='fas fa-exclamation-triangle'></i> ANOMALIES DÉTECTÉES</h3><ul style='margin-bottom: 25px; font-size: 14px; padding-left:25px; line-height:1.6;'>";
                foreach ($analysisData['anomalies'] as $item) { $html .= "<li style='margin-bottom:5px;'>$item</li>"; }
                $html .= "</ul>";

                $html .= "<h3 style='color: #3498db; margin-bottom:12px; font-size: 16px;'><i class='fas fa-chart-bar'></i> TENDANCES</h3><ul style='margin-bottom: 25px; font-size: 14px; padding-left:25px; line-height:1.6;'>";
                foreach ($analysisData['tendances'] as $item) { $html .= "<li style='margin-bottom:5px;'>$item</li>"; }
                $html .= "</ul>";

                $html .= "<h3 style='color: #f39c12; margin-bottom:12px; font-size: 16px;'><i class='fas fa-lightbulb'></i> RECOMMANDATIONS STRATÉGIQUES</h3><ul style='margin-bottom: 25px; font-size: 14px; padding-left:25px; line-height:1.6;'>";
                foreach ($analysisData['recommandations'] as $item) { $html .= "<li style='margin-bottom:5px;'>$item</li>"; }
                $html .= "</ul>";

                $html .= "<h3 style='color: #9b59b6; margin-bottom:12px; font-size: 16px;'><i class='fas fa-crystal-ball'></i> PRÉDICTIONS FUTURES</h3><ul style='margin-bottom: 5px; font-size: 14px; padding-left:25px; line-height:1.6;'>";
                foreach ($analysisData['predictions'] as $item) { $html .= "<li style='margin-bottom:5px;'>$item</li>"; }
                $html .= "</ul>";

                $html .= "</div>";

                return $html;
            }

            return "<p style='color:orange;'>L'IA n'a pas pu générer de réponse exploitable.</p>";
        } catch (\Exception $e) {
            $this->logger->error("Erreur Gemini Billets: " . $e->getMessage());
            return "<p style='color:red;'>Erreur système lors de l'analyse : " . $e->getMessage() . "</p>";
        }
    }
}
