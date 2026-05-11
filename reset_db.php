<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1", "root", "");
    $pdo->exec("DROP DATABASE IF EXISTS explora_db");
    $pdo->exec("CREATE DATABASE explora_db");
    echo "Base de données 'explora_db' réinitialisée avec succès !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
