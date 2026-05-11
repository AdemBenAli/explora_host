<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=explora_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Vérification des colonnes manquantes...\n";

    // 1. Ajout de created_at dans transport
    try {
        $pdo->exec("ALTER TABLE transport ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "- Colonne 'created_at' ajoutée à la table 'transport'.\n";
    } catch (Exception $e) {
        echo "- Colonne 'created_at' existe déjà ou erreur dans 'transport'.\n";
    }

    // 2. Toujours vérifier billet (très courant de l'avoir aussi)
    try {
        $pdo->exec("ALTER TABLE billet ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "- Colonne 'created_at' ajoutée à la table 'billet'.\n";
    } catch (Exception $e) {
        // Optionnel, on ne s'inquiète pas si elle y est déjà
    }

    echo "\nBase de données mise à jour avec succès !";

} catch (PDOException $e) {
    echo "Erreur fatale : " . $e->getMessage();
}
