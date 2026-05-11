<?php
$inputFile = 'explora_db.sql';
$outputFile = 'explora_db.sql';
$sql = file_get_contents($inputFile);

// Tables à conserver (Périmètre Transport)
$tablesToKeep = ['transport', 'billet', 'eco_scores', 'boutique_velo', 'code_promo_velo'];

$newContent = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$newContent .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$newContent .= "START TRANSACTION;\n";
$newContent .= "SET time_zone = \"+00:00\";\n\n";

foreach ($tablesToKeep as $table) {
    // 1. Extraction Structure
    if (preg_match("/CREATE TABLE `$table` .*?;/s", $sql, $matches)) {
        $tableDef = $matches[0];
        
        // On rajoute la Primary Key direct dans le CREATE pour éviter l'erreur 150
        // On cherche le nom de la PK dans les ALTER TABLE du fichier original
        if (preg_match("/ALTER TABLE `$table`[\s\n]+ADD PRIMARY KEY \(`(.*?)`\)/s", $sql, $pkMatches)) {
            $pkField = $pkMatches[1];
            $tableDef = str_replace("`$pkField` int(11) NOT NULL,", "`$pkField` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,", $tableDef);
        }
        
        $newContent .= "DROP TABLE IF EXISTS `$table`;\n";
        $newContent .= $tableDef . "\n\n";
    }

    // 2. Extraction Données (INSERT INTO)
    if (preg_match("/INSERT INTO `$table` .*?;/s", $sql, $matches)) {
        $newContent .= $matches[0] . "\n\n";
    }
}

// 3. Extraction des Index restants (sauf PK qu'on a déjà mis)
foreach ($tablesToKeep as $table) {
    if (preg_match_all("/ALTER TABLE `$table` .*?;/s", $sql, $matches)) {
        foreach ($matches[0] as $alter) {
            if (strpos($alter, 'ADD PRIMARY KEY') !== false) continue; // Déjà géré
            if (strpos($alter, 'MODIFY') !== false && strpos($alter, 'AUTO_INCREMENT') !== false) continue; // Déjà géré
            
            // On ne garde que les clés étrangères qui pointent vers nos tables conservées
            if (strpos($alter, 'CONSTRAINT') !== false && !preg_match("/REFERENCES `(" . implode('|', $tablesToKeep) . ")`/", $alter)) {
                continue; 
            }
            $newContent .= $alter . "\n";
        }
    }
}

$newContent .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
$newContent .= "COMMIT;\n";

file_put_contents($outputFile, $newContent);
echo "Base de données transport générée proprement !";
