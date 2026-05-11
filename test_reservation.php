<?php
require_once 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$dbUrl = $_ENV['DATABASE_URL'] ?? '';
preg_match('/mysql:\/\/(.*):(.*)@(.*)\/(.*)\?/', $dbUrl, $matches);

try {
    $pdo = new PDO("mysql:host={$matches[3]};dbname={$matches[4]}", $matches[1], $matches[2]);
    
    // 1. Lire les places avant
    $id = 22; // Transport Tunis -> Sfax
    $stmt = $pdo->prepare("SELECT places_disponibles FROM transport WHERE id = ?");
    $stmt->execute([$id]);
    $avant = $stmt->fetchColumn(); $avant = (int)$avant;
    
    // 2. Simuler une réservation de 2 places
    $nouveau = $avant - 2;
    $upd = $pdo->prepare("UPDATE transport SET places_disponibles = ? WHERE id = ?");
    $upd->execute([$nouveau, $id]);
    
    // 3. Lire les places après
    $stmt->execute([$id]);
    $apres = $stmt->fetchColumn(); $apres = (int)$apres;
    
    echo "Avant: $avant | Apres: $apres | Difference: " . ($avant - $apres) . "\n";
    
    // Remettre à niveau pour ne pas polluer
    $pdo->prepare("UPDATE transport SET places_disponibles = ? WHERE id = ?")->execute([$avant, $id]);

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
