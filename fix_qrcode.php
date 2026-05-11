<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=explora_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Vérification de la colonne 'qr_code'...\n";

    try {
        $pdo->exec("ALTER TABLE billet ADD qr_code VARCHAR(255) DEFAULT NULL AFTER statut");
        echo "- Colonne 'qr_code' ajoutée à la table 'billet'.\n";
    } catch (Exception $e) {
        echo "- Colonne 'qr_code' existe déjà ou erreur dans 'billet'.\n";
    }

    echo "\nBase de données mise à jour !";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
