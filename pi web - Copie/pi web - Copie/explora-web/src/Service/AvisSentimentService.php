<?php

namespace App\Service;

class AvisSentimentService
{
    private const API_ENDPOINT = 'https://api.api-ninjas.com/v1/sentiment';

    public function predictStars(string $comment): int
    {
        $comment = trim($comment);

        if ($comment === '') {
            return 0;
        }

        if (mb_strlen($comment) > 2000) {
            $comment = mb_substr($comment, 0, 2000);
        }

        $apiKey = (string) ($_ENV['API_NINJAS_KEY'] ?? $_SERVER['API_NINJAS_KEY'] ?? '');

        if ($apiKey !== '') {
            $apiScore = $this->fetchSentimentScoreFromApi($comment, $apiKey);
            if ($apiScore !== null) {
                return $this->scoreToStars($apiScore);
            }
        }

        return $this->scoreToStars($this->localSentimentScore($comment));
    }

    private function fetchSentimentScoreFromApi(string $text, string $apiKey): ?float
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $url = self::API_ENDPOINT . '?text=' . rawurlencode($text);

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);

        if (is_array($data) && isset($data['score']) && is_numeric($data['score'])) {
            return (float) $data['score'];
        }

        if (is_array($data) && isset($data['sentiment']) && is_string($data['sentiment'])) {
            return match (strtolower($data['sentiment'])) {
                'positive' => 0.7,
                'negative' => -0.7,
                default => 0.0,
            };
        }

        return null;
    }

    private function scoreToStars(float $score): int
    {
        $score = max(-1, min(1, $score));
        $mapped = 1 + (($score + 1) / 2) * 4;
        $stars = (int) round($mapped);

        return max(1, min(5, $stars));
    }

    private function localSentimentScore(string $text): float
    {
        $t = mb_strtolower($text);

        $positiveWords = [
            'love', 'perfect', 'excellent', 'amazing', 'great', 'wonderful', 'clean', 'friendly', 'nice',
            'awesome', 'good', 'super', 'top', 'best', 'fantastic', 'recommend',
            'j\'adore', 'parfait', 'excellent', 'incroyable', 'super', 'propre', 'gentil', 'sympa',
            'bien', 'recommande', 'magnifique', 'beau', 'confortable', 'luxueux',
        ];

        $negativeWords = [
            'terrible', 'awful', 'bad', 'dirty', 'rude', 'worst', 'horrible', 'disgusting', 'hate',
            'poor', 'noisy', 'problem', 'bugs', 'broken', 'slow', 'scam',
            'nul', 'mauvais', 'sale', 'pire', 'bruit', 'impoli', 'arnaque', 'catastrophe',
            'cold', 'décevant', 'decevant', 'froid', 'service nul',
        ];

        $positive = 0;
        $negative = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($t, $word)) {
                $positive++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($t, $word)) {
                $negative++;
            }
        }

        if ($positive === 0 && $negative === 0) {
            return 0.0;
        }

        $raw = ($positive - $negative) / max(1, ($positive + $negative));
        return max(-1, min(1, $raw));
    }
}