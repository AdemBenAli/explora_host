<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MeteoService
{
    private const API_KEY = 'a10624b2ab48908b9c284b2498298bb8';
    private const API_URL = 'https://api.openweathermap.org/data/2.5/forecast';

    private HttpClientInterface $httpClient;
    private CacheInterface $cache;

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * Récupère la météo pour une ville et une date données.
     */
    public function getMeteo(?string $ville, ?\DateTimeInterface $date): ?array
    {
        if (empty($ville) || !$date) {
            return null;
        }

        // Vérifier que la date est dans les 5 prochains jours
        $today = new \DateTime('today');
        $dateCheck = clone $date;
        $dateCheck->setTime(0, 0, 0);

        if ($dateCheck < $today || $dateCheck > (clone $today)->modify('+5 days')) {
            return null; // Hors plage gratuite d'OpenWeatherMap (5 jours max)
        }

        $cacheKey = 'meteo_' . md5(strtolower($ville) . '_' . $date->format('Y-m-d'));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($ville, $date) {
            $item->expiresAfter(3600); // Mettre en cache pendant 1 heure

            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => [
                        'q' => $ville,
                        'appid' => self::API_KEY,
                        'units' => 'metric',
                        'lang' => 'fr',
                        'cnt' => 40 // 5 jours × 8 créneaux/jour
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();
                    return $this->extractBestMatch($data, $date);
                }
            } catch (\Exception $e) {
                // En cas d'erreur de connexion ou autre
                return null;
            }

            return null;
        });
    }

    private function extractBestMatch(array $data, \DateTimeInterface $targetDate): ?array
    {
        $targetDateStr = $targetDate->format('Y-m-d');
        $bestMatch = null;
        $bestHourDiff = PHP_INT_MAX;

        foreach ($data['list'] ?? [] as $creneau) {
            $dtTxt = $creneau['dt_txt'] ?? ''; // ex: "2026-03-15 12:00:00"
            
            if (str_starts_with($dtTxt, $targetDateStr)) {
                $hour = (int) substr($dtTxt, 11, 2);
                $diff = abs($hour - 12); // Chercher le créneau le plus proche de midi

                if ($diff < $bestHourDiff) {
                    $bestHourDiff = $diff;
                    $bestMatch = $creneau;
                }
            }
        }

        if (!$bestMatch) {
            return null;
        }

        $temp = round($bestMatch['main']['temp'] ?? 0);
        $description = $bestMatch['weather'][0]['description'] ?? 'N/A';
        $description = ucfirst($description);
        $icon = $bestMatch['weather'][0]['icon'] ?? '01d';
        $humidity = $bestMatch['main']['humidity'] ?? 0;
        $windSpeed = round($bestMatch['wind']['speed'] ?? 0);

        return [
            'temp' => $temp,
            'description' => $description,
            'icon' => $icon,
            'emoji' => self::iconToEmoji($icon),
            'humidity' => $humidity,
            'windSpeed' => $windSpeed,
            'displayString' => self::iconToEmoji($icon) . ' ' . $temp . '°C · ' . $description
        ];
    }

    public static function iconToEmoji(string $icon): string
    {
        $prefix = substr($icon, 0, 2);
        return match ($prefix) {
            '01' => '☀️',
            '02' => '⛅',
            '03' => '🌥️',
            '04' => '☁️',
            '09' => '🌧️',
            '10' => '🌦️',
            '11' => '⛈️',
            '13' => '❄️',
            '50' => '🌫️',
            default => '🌡️',
        };
    }
}
