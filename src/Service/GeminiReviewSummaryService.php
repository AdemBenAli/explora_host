<?php

namespace App\Service;

class GeminiReviewSummaryService
{
    private ?string $apiKey = null;
    private ?string $resolvedModel = null;

    public function __construct()
    {
        $this->apiKey = trim((string) ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? ''));
    }

    public function generateSummaryText(array $comments): string
    {
        $comments = array_values(array_filter(array_map(
            static fn ($comment) => trim((string) $comment),
            $comments
        )));

        if ($comments === []) {
            return 'Aucun avis pour cet hébergement.';
        }

        if ($this->apiKey === null || $this->apiKey === '') {
            return '❌ GEMINI_API_KEY manquante. Ajoute la clé dans le fichier .env.';
        }

        $reviewsText = $this->joinReviews($comments);
        $json = $this->summarizeReviewsToJson($reviewsText);

        return $this->formatJsonForUi($json);
    }

    private function summarizeReviewsToJson(string $reviewsText): string
    {
        $model = $this->resolveModel();

        if ($model === null || $model === '') {
            throw new \RuntimeException('Impossible de résoudre un modèle Gemini compatible.');
        }

        $prompt = <<<PROMPT
Tu es un assistant pour une application de réservation appelée Explora.

Analyse les avis suivants et retourne UNIQUEMENT un JSON valide avec exactement cette structure :

{
  "points_forts": ["..."],
  "points_faibles": ["..."],
  "recommande_pour": ["..."],
  "note_globale": 0,
  "justification": "..."
}

Règles :
- points_forts : 2 à 5 éléments maximum
- points_faibles : 2 à 5 éléments maximum
- recommande_pour : 1 à 3 éléments maximum
- note_globale : nombre entre 0 et 5
- justification : une phrase courte
- ne retourne aucun texte hors JSON

AVIS :
{$reviewsText}
PROMPT;

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/%s:generateContent?key=%s',
            $model,
            rawurlencode($this->apiKey ?? '')
        );

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        $response = $this->postJson($url, $payload);

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \RuntimeException('Réponse Gemini invalide.');
        }

        $text = trim((string) $response['candidates'][0]['content']['parts'][0]['text']);

        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            return substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        return $text;
    }

    private function resolveModel(): ?string
    {
        if ($this->resolvedModel !== null) {
            return $this->resolvedModel;
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models?key=%s',
            rawurlencode($this->apiKey ?? '')
        );

        $response = $this->getJson($url);

        if (!isset($response['models']) || !is_array($response['models'])) {
            $this->resolvedModel = 'models/gemini-2.5-flash';
            return $this->resolvedModel;
        }

        foreach ($response['models'] as $model) {
            if (
                isset($model['name'], $model['supportedGenerationMethods']) &&
                is_string($model['name']) &&
                is_array($model['supportedGenerationMethods']) &&
                in_array('generateContent', $model['supportedGenerationMethods'], true)
            ) {
                $this->resolvedModel = $model['name'];
                return $this->resolvedModel;
            }
        }

        $this->resolvedModel = 'models/gemini-2.5-flash';
        return $this->resolvedModel;
    }

    private function formatJsonForUi(string $json): string
    {
        $pointsForts = $this->extractArray($json, 'points_forts');
        $pointsFaibles = $this->extractArray($json, 'points_faibles');
        $recommandePour = $this->extractArray($json, 'recommande_pour');
        $noteGlobale = $this->extractNumber($json, 'note_globale');
        $justification = $this->extractString($json, 'justification');

        $out = [];
        $out[] = "✅ Points forts:";
        $out[] = $this->arrayToBullets($pointsForts);
        $out[] = "";
        $out[] = "⚠️ Points faibles:";
        $out[] = $this->arrayToBullets($pointsFaibles);
        $out[] = "";
        $out[] = "🎯 Recommandé pour:";
        $out[] = $this->arrayToBullets($recommandePour);
        $out[] = "";

        if ($noteGlobale !== '') {
            $out[] = "⭐ Note globale: {$noteGlobale}/5";
        }

        if ($justification !== '') {
            $out[] = "📝 {$justification}";
        }

        return trim(implode("\n", $out));
    }

    /**
     * @return string[]
     */
    private function extractArray(string $json, string $key): array
    {
        if (!preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*\[(.*?)\]/su', $json, $matches)) {
            return [];
        }

        $inside = $matches[1];
        preg_match_all('/"(.*?)"/su', $inside, $items);

        return array_values(array_filter(array_map(
            static fn ($item) => trim(str_replace(['\"', '\n', '\r', '\t'], ['"', "\n", '', ''], $item)),
            $items[1] ?? []
        )));
    }

    private function extractNumber(string $json, string $key): string
    {
        if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*(-?\d+(?:\.\d+)?)/u', $json, $matches)) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private function extractString(string $json, string $key): string
    {
        if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*"(.*?)"/su', $json, $matches)) {
            return trim(str_replace(['\"', '\n', '\r', '\t'], ['"', "\n", '', ''], (string) $matches[1]));
        }

        return '';
    }

    private function arrayToBullets(array $items): string
    {
        if ($items === []) {
            return "• (vide)";
        }

        return implode("\n", array_map(
            static fn ($item) => '• ' . $item,
            $items
        ));
    }

    private function joinReviews(array $comments): string
    {
        $out = [];
        $index = 1;

        foreach ($comments as $comment) {
            if ($comment === '') {
                continue;
            }

            if (mb_strlen($comment) > 400) {
                $comment = mb_substr($comment, 0, 400) . '...';
            }

            $out[] = $index . ') ' . $comment;
            $index++;
        }

        return implode("\n", $out);
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $url): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL n’est pas disponible sur ce serveur.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Impossible d’initialiser cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Réponse vide Gemini. ' . $error);
        }

        $decoded = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Gemini HTTP ' . $httpCode . ' => ' . $raw);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Réponse JSON Gemini invalide.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL n’est pas disponible sur ce serveur.');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Impossible d’initialiser cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Réponse vide Gemini. ' . $error);
        }

        $decoded = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Gemini HTTP ' . $httpCode . ' => ' . $raw);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Réponse JSON Gemini invalide.');
        }

        return $decoded;
    }
}