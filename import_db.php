<?php
$host = '127.0.0.1';
$db   = 'explora_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $sql = file_get_contents('explora.sql');
     
     // Drop existing tables to avoid conflict
     $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
     $pdo->exec("DROP TABLE IF EXISTS reservation_guest;");
     $pdo->exec("DROP TABLE IF EXISTS reservation;");
     $pdo->exec("DROP TABLE IF EXISTS avis;");
     $pdo->exec("DROP TABLE IF EXISTS hebergement;");
     $pdo->exec("DROP TABLE IF EXISTS doctrine_migration_versions;");
     $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
     
     // Remove SET commands that might fail
     $sql = preg_replace('/^SET .*$/m', '', $sql);
     
     $pdo->exec($sql);
     echo "SQL imported successfully!\n";
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
