<?php

namespace App\Service;

use App\Entity\Voyage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private const GEMINI_MODEL = 'gemini-2.5-flash';
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private const API_KEY = 'AIzaSyCf27NUT16mbYgfthqluyQJIv8bkpndfz4';
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=';
    private const DEFAULT_GEMINI_API_KEY = 'AIzaSyD_QHytuFMJCbFTPiM93SJ9S0J0VA59w84';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    // ── Methods from group work ─────────────────────────────────────────────

    public function generateContent(string $prompt): string
    {
        $apiKey = trim((string) ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is missing. Configure GEMINI_API_KEY.');
        }

        $response = $this->httpClient->request('POST', self::GEMINI_API_URL . '?key=' . urlencode($apiKey), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                ],
            ],
            'timeout' => 35,
        ]);

        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status !== 200) {
            $error = is_array($data) ? json_encode($data) : 'unknown error';
            throw new \RuntimeException('Gemini API error (' . $status . '): ' . $error);
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new \RuntimeException('Gemini API returned no recommendation text.');
        }

        return trim($text);
    }

    public function genererDescription(string $nom, string $ville, string $categorie): ?string
    {
        $prompt = "Rédige une description de 3 lignes maximum pour l'activité : "
            . $nom . " à " . $ville . ". Catégorie : " . $categorie . ". "
            . "Sois invitant et professionnel. Réponds uniquement avec la description.";

        $ch = curl_init(self::API_URL . self::API_KEY);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        throw new \RuntimeException('Erreur API Gemini HTTP ' . $httpCode . ': ' . $response);
    }

    // ── Methods from my work (Voyage AI features) ───────────────────────────

    public function generateDescription(string $titre, string $destination): string
    {
        $prompt = "Tu es un copywriter expert en voyage. Rédige une description très vendeuse, captivante et courte (max 3-4 phrases) pour un voyage intitulé '$titre' à destination de '$destination'. Ne mets pas de formatage Markdown comme du gras ou des titres, juste le texte.";
        return $this->callGemini($prompt);
    }

    /**
     * @param string $query
     * @param Voyage[] $voyages
     * @return int[]
     */
    public function smartSearch(string $query, array $voyages): array
    {
        $voyagesList = [];
        foreach ($voyages as $v) {
            $voyagesList[] = "- ID: {$v->getId()} | Titre: {$v->getTitre()} | Dest: {$v->getDestination()} | Prix: {$v->getBudgetTotal()} | Durée: {$v->getDureeJours()}";
        }
        $voyagesText = implode("\n", $voyagesList);

        $prompt = "Tu es un agent de voyage. Voici la liste des voyages disponibles:\n$voyagesText\n\nLe client recherche ceci : '$query'.\nTrouve les meilleurs voyages qui correspondent à cette demande. Retourne UNIQUEMENT une liste JSON des IDs des voyages qui correspondent, par exemple [1, 5, 8]. Ne dis rien d'autre, just le JSON.";

        $response = $this->callGemini($prompt);
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $ids = json_decode($response, true);
        if (is_array($ids)) {
            return array_map('intval', $ids);
        }
        return [];
    }

    /**
     * @param string $message
     * @param Voyage[] $voyages
     */
    public function chat(string $message, array $voyages): string
    {
        $voyagesList = [];
        foreach ($voyages as $v) {
            $voyagesList[] = "- {$v->getTitre()} à {$v->getDestination()} (Prix: {$v->getBudgetTotal()}€, Durée: {$v->getDureeJours()}j)";
        }
        $voyagesText = implode("\n", $voyagesList);

        $prompt = "Tu es un assistant virtuel pour l'agence de voyage 'Explora'. Réponds de façon concise, polie et enthousiaste.\nVoici la liste de nos voyages actuels :\n$voyagesText\n\nQuestion du client : '$message'\nRéponds à la question en te basant sur nos voyages. Si la question n'est pas liée au voyage, recadre poliment.";

        return $this->callGemini($prompt);
    }

    // ── Private helper ──────────────────────────────────────────────────────

    private function callGemini(string $prompt): string
    {
        $apiKey = (string) ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? self::DEFAULT_GEMINI_API_KEY);

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            rawurlencode($apiKey)
        );

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $responseBody = curl_exec($ch);

        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return "Désolé, je ne peux pas me connecter à l'IA pour le moment. Erreur locale : " . $error;
        }

        curl_close($ch);

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded) || !isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            return "Une erreur est survenue lors de la génération de la réponse.";
        }

        return $decoded['candidates'][0]['content']['parts'][0]['text'];
    }
}
