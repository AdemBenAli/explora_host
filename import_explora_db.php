<?php
// import_explora_db.php

$sqlFile = 'explora.sql';
if (!file_exists($sqlFile)) {
    die("Fichier $sqlFile introuvable.\n");
}

// Récupération des paramètres DB depuis .env
$env = file_get_contents('.env');
preg_match('/^DATABASE_URL="mysql:\/\/([^:]*):([^@]*)@([^:]+):(\d+)\/([^?"]+)/m', $env, $matches);

if (!$matches) {
    die("Impossible de lire DATABASE_URL dans .env\n");
}

$user = $matches[1];
$pass = $matches[2];
$host = $matches[3];
$port = $matches[4];
$db   = $matches[5];

echo "Importation de $sqlFile dans la base $db ($host:$port)...\n";

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Supprimer et recréer la base pour être sûr d'avoir les bonnes tables
    $pdo->exec("DROP DATABASE IF EXISTS `$db` ");
    $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db` ");

    $sql = file_get_contents($sqlFile);
    
    // Méthode split simple
    $queries = explode(";\n", $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            $pdo->exec($query);
        }
    }

    echo "Importation terminée avec succès.\n";
} catch (Exception $e) {
    die("Erreur lors de l'importation : " . $e->getMessage() . "\n");
}
