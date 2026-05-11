<?php

namespace App\Service;

final class MapApiService
{
    public function __construct(
        private readonly string $openRouteApiKey = ''
    ) {
    }

    public function buildRouteByCity(string $origin, string $destination): ?array
    {
        $origin = $this->normalizeCity($origin);
        $destination = $this->normalizeCity($destination);

        if ($origin == '' || $destination == '') {
            return null;
        }
        if (mb_strtolower($origin) === mb_strtolower($destination)) {
            return null;
        }

        $from = $this->geocodeCity($origin);
        $to = $this->geocodeCity($destination);
        if ($from === null || $to === null) {
            return null;
        }

        $route = $this->getRoute($from['lon'], $from['lat'], $to['lon'], $to['lat']);
        if ($route === null) {
            return null;
        }

        return [
            'origin' => ['label' => $origin, 'lat' => $from['lat'], 'lon' => $from['lon']],
            'destination' => ['label' => $destination, 'lat' => $to['lat'], 'lon' => $to['lon']],
            'route' => $route,
        ];
    }

    private function geocodeCity(string $query): ?array
    {
        if (!$this->isValidCityInput($query)) {
            return null;
        }

        $queryUpper = mb_strtoupper(trim($query));
        
        // ── MANUAL COORDINATES FALLBACK (High Precision for Tunisia) ──
        $manualCoords = [
            'TUNIS' => ['lat' => 36.8065, 'lon' => 10.1815],
            'TUNISIE' => ['lat' => 36.8065, 'lon' => 10.1815], // Default "Tunisie" to Tunis Capital
            'SOUSSE' => ['lat' => 35.8256, 'lon' => 10.6369],
            'MONASTIR' => ['lat' => 35.7833, 'lon' => 10.8333],
            'HAMMAMET' => ['lat' => 36.3944, 'lon' => 10.5850],
            'DJERBA' => ['lat' => 33.8075, 'lon' => 10.8451],
            'SFAX' => ['lat' => 34.7400, 'lon' => 10.7600],
            'GABES' => ['lat' => 33.8814, 'lon' => 10.0982],
            'BIZERTE' => ['lat' => 37.2744, 'lon' => 9.8739],
            'TOZEUR' => ['lat' => 33.9197, 'lon' => 8.1335],
            'NABEUL' => ['lat' => 36.4561, 'lon' => 10.7376],
            'MAHDIA' => ['lat' => 35.5047, 'lon' => 11.0622],
        ];

        if (isset($manualCoords[$queryUpper])) {
            return $manualCoords[$queryUpper];
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $data = $this->getJson($url);

        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    private function getRoute(float $startLon, float $startLat, float $endLon, float $endLat): ?array
    {
        $openRoute = $this->getRouteFromOpenRoute($startLon, $startLat, $endLon, $endLat);
        if ($openRoute !== null) {
            return $openRoute;
        }

        return $this->getRouteFromOsrm($startLon, $startLat, $endLon, $endLat);
    }

    private function getRouteFromOpenRoute(float $startLon, float $startLat, float $endLon, float $endLat): ?array
    {
        if ($this->openRouteApiKey === '') {
            return null;
        }

        $url = 'https://api.openrouteservice.org/v2/directions/driving-car';
        $body = json_encode([
            'coordinates' => [
                [$startLon, $startLat],
                [$endLon, $endLat],
            ],
        ]);

        if ($body === false) {
            return null;
        }

        $response = $this->postJson($url, $body, [
            'Authorization: ' . $this->openRouteApiKey,
        ]);

        if (!is_array($response) || empty($response['features'][0])) {
            return null;
        }

        return $response;
    }

    private function getRouteFromOsrm(float $startLon, float $startLat, float $endLon, float $endLat): ?array
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=full&geometries=geojson',
            $startLon,
            $startLat,
            $endLon,
            $endLat
        );

        $data = $this->getJson($url);
        if (!is_array($data) || empty($data['routes'][0]['geometry']['coordinates'])) {
            return null;
        }

        $route = $data['routes'][0];

        return [
            'features' => [[
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $route['geometry']['coordinates'],
                ],
                'properties' => [
                    'segments' => [[
                        'distance' => (float) ($route['distance'] ?? 0),
                        'duration' => (float) ($route['duration'] ?? 0),
                    ]],
                ],
            ]],
            'source' => 'osrm',
        ];
    }

    private function getJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Explora-Web/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function postJson(string $url, string $jsonBody, array $extraHeaders = []): ?array
    {
        $headers = array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Explora-Web/1.0',
        ], $extraHeaders);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers) . "\r\n",
                'content' => $jsonBody,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function isValidCityInput(string $value): bool
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 80) {
            return false;
        }

        if (!preg_match('/^[\p{L}\s\'\-]+$/u', $value)) {
            return false;
        }

        return !preg_match('/\d/u', $value);
    }

    private function normalizeCity(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return $value ?? '';
    }
}
