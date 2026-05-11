<?php
$filePath = __DIR__ . '/templates/admin_transport/index.html.twig';
$content = file_get_contents($filePath);

// 1. Réparer la flèche brisée dans le tableau
$content = str_replace('â†’', '<i class="fas fa-arrow-right" style="color:var(--gray); font-size:10px; margin:0 5px;"></i>', $content);
$content = str_replace('â€”', '—', $content);

// 2. Réparer les en-têtes avec une regex flexible (ignore les caractères entre <th> et Retard IA)
$content = preg_replace('/<th rowspan="2">.*?Retard IA<\/th>/i', '<th rowspan="2"><i class="fas fa-robot"></i> Retard IA</th>', $content);
$content = preg_replace('/<th rowspan="2">.*?N.*?Vol<\/th>/i', '<th rowspan="2">N&deg; Vol</th>', $content);

// 3. Réparer "Départ" et "Arrivée" si encore cassés
$content = preg_replace('/<th.*?D.*?part<\/th>/i', '<th colspan="2" class="group-header"><i class="fas fa-plane-departure"></i> Départ</th>', $content);
$content = preg_replace('/<th.*?Arriv.*?e<\/th>/i', '<th colspan="2" class="group-header"><i class="fas fa-plane-arrival"></i> Arrivée</th>', $content);

// 4. Nettoyer les commentaires brisés en début de fichier
$content = preg_replace('/\/\* â•.*?â•  \*\//s', '/* ============================================================= */', $content);

file_put_contents($filePath, $content);
echo "Correction terminée avec succès !";
