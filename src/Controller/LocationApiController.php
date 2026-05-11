<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LocationApiController extends AbstractController
{
    private const COUNTRIES_API_URL = 'https://countriesnow.space/api/v0.1/countries';
    private const GEOAPIFY_AUTOCOMPLETE_URL = 'https://api.geoapify.com/v1/geocode/autocomplete';

    #[Route('/api/countries', name: 'api_countries', methods: ['GET'])]
    public function apiCountries(): JsonResponse
    {
        $payload = $this->fetchJson(self::COUNTRIES_API_URL);
        $rows = $payload['data'] ?? [];

        $countries = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['country'] ?? ''));
            $code = strtoupper(trim((string) ($row['iso2'] ?? '')));

            if ($name === '' || $code === '') {
                continue;
            }

            $countries[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        usort($countries, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->json($countries);
    }

    #[Route('/api/cities', name: 'api_cities', methods: ['GET'])]
    public function apiCities(Request $request): JsonResponse
    {
        $country = strtoupper(trim((string) $request->query->get('country', '')));
        if ($country === '') {
            return $this->json([]);
        }

        $payload = $this->fetchJson(self::COUNTRIES_API_URL);
        $rows = $payload['data'] ?? [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $iso2 = strtoupper(trim((string) ($row['iso2'] ?? '')));
            if ($iso2 !== $country) {
                continue;
            }

            $cities = array_values(array_filter(array_map(static fn($city): string => trim((string) $city), (array) ($row['cities'] ?? []))));
            sort($cities, SORT_NATURAL | SORT_FLAG_CASE);

            return $this->json($cities);
        }

        return $this->json([]);
    }

    #[Route('/api/address-suggestions', name: 'api_address_suggestions', methods: ['GET'])]
    public function apiAddressSuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if ($query === '' || mb_strlen($query) < 3) {
            return $this->json([]);
        }

        $apiKey = trim((string) ($_ENV['GEOAPIFY_API_KEY'] ?? $_SERVER['GEOAPIFY_API_KEY'] ?? 'ad30888ce5f54ebcb004d037f194a164'));
        if ($apiKey === '') {
            return $this->json([]);
        }

        $url = sprintf('%s?text=%s&apiKey=%s&limit=6', self::GEOAPIFY_AUTOCOMPLETE_URL, rawurlencode($query), rawurlencode($apiKey));
        $payload = $this->fetchJson($url);

        $features = $payload['features'] ?? [];
        $suggestions = [];

        foreach ($features as $feature) {
            if (!is_array($feature)) {
                continue;
            }

            $formatted = trim((string) (($feature['properties']['formatted'] ?? '') ?: ''));
            if ($formatted !== '') {
                $suggestions[] = $formatted;
            }
        }

        return $this->json(array_values(array_unique($suggestions)));
    }

    private function fetchJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
