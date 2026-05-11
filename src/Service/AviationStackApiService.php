<?php

namespace App\Service;

final class AviationStackApiService
{
    private const BASE_URL = 'http://api.aviationstack.com/v1/flights';

    public function __construct(
        private readonly string $aviationStackApiKey = ''
    ) {
    }

    public function searchFlights(string $departureIata, ?string $arrivalIata = null): array
    {
        if ($this->aviationStackApiKey === '') {
            return [];
        }

        $params = [
            'access_key' => $this->aviationStackApiKey,
            'dep_iata' => strtoupper(trim($departureIata)),
            'limit' => 25,
        ];

        $arrivalIata = $arrivalIata !== null ? strtoupper(trim($arrivalIata)) : null;
        if ($arrivalIata !== null && $arrivalIata !== '') {
            $params['arr_iata'] = $arrivalIata;
        }

        $url = self::BASE_URL . '?' . http_build_query($params);
        $json = $this->getJson($url);
        if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
            return [];
        }

        $flights = [];
        foreach ($json['data'] as $flight) {
            if (!is_array($flight)) {
                continue;
            }

            $departure = is_array($flight['departure'] ?? null) ? $flight['departure'] : [];
            $arrival = is_array($flight['arrival'] ?? null) ? $flight['arrival'] : [];
            $airline = is_array($flight['airline'] ?? null) ? $flight['airline'] : [];
            $flightObj = is_array($flight['flight'] ?? null) ? $flight['flight'] : [];

            $departureAirport = (string) ($departure['airport'] ?? 'Aeroport inconnu');
            $arrivalAirport = (string) ($arrival['airport'] ?? 'Aeroport inconnu');
            $flightNumber = (string) ($flightObj['iata'] ?? ($flight['flight_number'] ?? 'N/A'));

            [$lat, $lon] = $this->pickCoordinatesByIata(
                (string) ($departure['iata'] ?? ''),
                (string) ($arrival['iata'] ?? '')
            );

            $flights[] = [
                'flight_number' => $flightNumber,
                'airline' => (string) ($airline['name'] ?? 'Compagnie inconnue'),
                'departure' => $this->extractCity($departureAirport),
                'arrival' => $this->extractCity($arrivalAirport),
                'departure_time' => (string) ($departure['scheduled'] ?? ''),
                'latitude' => $lat,
                'longitude' => $lon,
                'altitude' => '10000 m',
                'speed' => '850 km/h',
            ];
        }

        return $flights;
    }

    private function pickCoordinatesByIata(string $depIata, string $arrIata): array
    {
        $coords = [
            'TUN' => [36.8510, 10.2272],
            'MIR' => [35.7581, 10.7547],
            'DJE' => [33.8750, 10.7755],
            'SFA' => [34.7179, 10.6908],
            'TOE' => [33.9397, 8.1106],
            'CDG' => [49.0097, 2.5479],
            'ORY' => [48.7262, 2.3652],
            'MRS' => [43.4393, 5.2214],
            'LYS' => [45.7256, 5.0811],
            'LHR' => [51.4700, -0.4543],
            'FCO' => [41.8003, 12.2389],
            'DXB' => [25.2532, 55.3657],
            'IST' => [41.2753, 28.7519],
        ];

        $depIata = strtoupper($depIata);
        $arrIata = strtoupper($arrIata);
        if (isset($coords[$depIata]) && isset($coords[$arrIata])) {
            $from = $coords[$depIata];
            $to = $coords[$arrIata];

            $lat = (($from[0] + $to[0]) / 2) + (mt_rand(-20, 20) / 100);
            $lon = (($from[1] + $to[1]) / 2) + (mt_rand(-20, 20) / 100);
            return [round($lat, 4), round($lon, 4)];
        }

        return [36.8 + (mt_rand(0, 100) / 100), 10.2 + (mt_rand(0, 100) / 100)];
    }

    private function extractCity(string $airportName): string
    {
        if ($airportName === '') {
            return 'Ville inconnue';
        }

        $city = preg_replace('/(?i)\s*international airport$/', '', $airportName);
        $city = preg_replace('/(?i)\s*airport$/', '', (string) $city);
        $city = trim((string) $city);
        $parts = preg_split('/\s+/', $city);

        return $parts !== false && isset($parts[0]) ? $parts[0] : $city;
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
}

