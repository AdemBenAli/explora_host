<?php

$dsn = 'mysql:host=127.0.0.1;dbname=explora_db;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Try to drop the table if it still exists
    try {
        $pdo->exec('DROP TABLE IF EXISTS `utilisateur`');
        echo "Table dropped or did not exist.\n";
    } catch (Exception $e) {
        echo "Warning during drop: " . $e->getMessage() . "\n";
    }

    $sql = "CREATE TABLE `utilisateur` (
      id INT AUTO_INCREMENT NOT NULL, 
      bio VARCHAR(255) DEFAULT NULL, 
      dateCreation DATETIME NOT NULL, 
      email VARCHAR(255) NOT NULL, 
      estVerifie VARCHAR(255) NOT NULL, 
      motDePasse VARCHAR(255) NOT NULL, 
      nationalite VARCHAR(255) DEFAULT NULL, 
      nom VARCHAR(255) NOT NULL, 
      photoDeProfil VARCHAR(255) DEFAULT NULL, 
      prenom VARCHAR(255) NOT NULL, 
      role VARCHAR(255) NOT NULL, 
      statut VARCHAR(255) NOT NULL, 
      telephone INT NOT NULL, 
      adresse VARCHAR(255) DEFAULT NULL, 
      codePostale VARCHAR(255) DEFAULT NULL, 
      dateNaissance DATETIME DEFAULT NULL, 
      pays VARCHAR(255) DEFAULT NULL, 
      ville VARCHAR(255) DEFAULT NULL, 
      PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;";

    $pdo->exec($sql);
    echo "Table 'utilisateur' created successfully.\n";

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
