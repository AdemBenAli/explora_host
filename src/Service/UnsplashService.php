<?php

namespace App\Service;

class UnsplashService
{
    private const ACCESS_KEY = 'OGDCEzt6EedRWt1OHIRPJRSRjtJOWqV4KvNfXsvinxI';
    private const API_URL    = 'https://api.unsplash.com/search/photos';
    private const IMAGES_DIR = 'uploads/images/';

    public function fetchAndSavePhoto(string $query): ?string
    {
        $apiUrl = self::API_URL . '?query=' . urlencode($query) . '&per_page=1&orientation=landscape';

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Client-ID ' . self::ACCESS_KEY],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $imageUrl = $this->extractImageUrl($response);
        if (!$imageUrl) {
            return null;
        }

        return $this->downloadImage($imageUrl, $query);
    }

    private function extractImageUrl(string $json): ?string
    {
        $data = json_decode($json, true);
        return $data['results'][0]['urls']['regular'] ?? null;
    }

    private function downloadImage(string $imageUrl, string $queryHint): ?string
    {
        $dir = $this->getPublicDir() . self::IMAGES_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $safeName  = preg_replace('/[^a-z0-9]/', '_', strtolower($queryHint));
        $fileName  = 'activite_' . $safeName . '_' . substr(uniqid(), -8) . '.jpg';
        $localPath = $dir . $fileName;

        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'SymfonyApp/1.0',
        ]);

        $imageData = curl_exec($ch);
        curl_close($ch);

        if (!$imageData) {
            return null;
        }

        file_put_contents($localPath, $imageData);

        // Retourne le chemin relatif pour stocker en BDD
        return self::IMAGES_DIR . $fileName;
    }

    private function getPublicDir(): string
    {
        return dirname(__DIR__, 2) . '/public/';
    }

    public static function buildSearchQuery(string $nom, string $ville, string $categorie): string
    {
        $parts = [];

        if (!empty(trim($nom))) {
            $mots = explode(' ', trim($nom));
            $parts[] = $mots[0];
            if (isset($mots[1])) $parts[] = $mots[1];
        }

        if (!empty(trim($ville))) {
            $parts[] = trim($ville);
        }

        if (empty($parts) && !empty($categorie)) {
            $parts[] = strtolower($categorie);
        }

        return empty($parts) ? 'travel activity' : implode(' ', $parts);
    }
}