<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class WeatherApiService
{
    private $cache;
    private static $requestLocalCache = [];

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getWeatherByCity(string $city): ?array
    {
        $city = trim($city);
        if ($city == '') {
            return null;
        }

        $cityLower = mb_strtolower($city, 'UTF-8');
        if (isset(self::$requestLocalCache[$cityLower])) {
            return self::$requestLocalCache[$cityLower];
        }

        $cacheKey = 'weather_' . md5($cityLower);

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
            $item->expiresAfter(1800); // 30 minutes
            
            $geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?name='
                . rawurlencode($city)
                . '&count=1&language=fr&format=json';

            $geoData = $this->getJson($geoUrl);
            if (!is_array($geoData) || empty($geoData['results'][0])) {
                return null;
            }

            $location = $geoData['results'][0];
            $lat = (float) ($location['latitude'] ?? 0);
            $lon = (float) ($location['longitude'] ?? 0);
            $country = (string) ($location['country'] ?? '');

            $meteoUrl = 'https://api.open-meteo.com/v1/forecast?latitude='
                . $lat
                . '&longitude='
                . $lon
                . '&current_weather=true&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto';

            $meteoData = $this->getJson($meteoUrl);
            if (!is_array($meteoData) || empty($meteoData['current_weather'])) {
                return null;
            }

            $current = $meteoData['current_weather'];
            $daily = $meteoData['daily'] ?? [];
            $dates = $daily['time'] ?? [];
            $maxTemps = $daily['temperature_2m_max'] ?? [];
            $minTemps = $daily['temperature_2m_min'] ?? [];
            $codes = $daily['weathercode'] ?? [];

            $forecasts = [];
            $forecastCount = min(7, count($dates), count($maxTemps), count($minTemps), count($codes));
            for ($i = 0; $i < $forecastCount; $i++) {
                $forecasts[] = [
                    'date' => (string) $dates[$i],
                    'maxTemp' => (float) $maxTemps[$i],
                    'minTemp' => (float) $minTemps[$i],
                    'weatherCode' => (int) $codes[$i],
                ];
            }

            return [
                'ville' => $city,
                'pays' => $country,
                'temperature' => (float) ($current['temperature'] ?? 0),
                'weatherCode' => (int) ($current['weathercode'] ?? -1),
                'forecasts' => $forecasts,
            ];
        });

        // Ne pas garder en cache les résultats null (échecs API)
        // pour réessayer lors de la prochaine requête
        if ($data === null) {
            $this->cache->delete($cacheKey);
        }

        self::$requestLocalCache[$cityLower] = $data;
        return $data;
    }

    private function getJson(string $url): ?array
    {
        // Méthode 1 : cURL (plus fiable pour HTTPS sur Windows/XAMPP)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: Explora-Web/1.0',
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($raw !== false && $httpCode >= 200 && $httpCode < 400) {
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : null;
            }
        }

        // Méthode 2 : file_get_contents (fallback avec contexte SSL)
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Explora-Web/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}

