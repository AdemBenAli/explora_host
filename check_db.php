<?php
require_once 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$dbUrl = $_ENV['DATABASE_URL'] ?? '';
// mysql://root:@127.0.0.1:3306/explora_db?serverVersion=8.0.32&charset=utf8mb4
preg_match('/mysql:\/\/(.*):(.*)@(.*)\/(.*)\?/', $dbUrl, $matches);

if (!$matches) {
    die("Format DATABASE_URL invalide.\n");
}

try {
    $pdo = new PDO("mysql:host={$matches[3]};dbname={$matches[4]}", $matches[1], $matches[2]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, origine, destination, date_depart FROM transport ORDER BY id DESC LIMIT 10");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "ID: " . $r['id'] . " | " . $r['origine'] . " -> " . $r['destination'] . " | Date: " . $r['date_depart'] . "\n";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
