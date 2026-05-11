<?php

$files = [
    'templates/admin_transport/index.html.twig',
    'templates/admin/sidebar.html.twig',
    'templates/partials/sidebar.html.twig',
    'templates/admin_billet/index.html.twig',
    'templates/admin_base.html.twig',
    'templates/base_admin.html.twig'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $replacements = [
            'DÃ©part' => 'Départ',
            'ArrivÃ©e' => 'Arrivée',
            'GÃ©rez' => 'Gérez',
            'RÃ©clamations' => 'Réclamations',
            'ParamÃ¨tres' => 'Paramètres',
            'DÃ©connexion' => 'Déconnexion',
            'HÃ´tels' => 'Hôtels',
            'SYSTÃˆME' => 'SYSTÈME',
            'RÃ©partition' => 'Répartition',
            'rÃ©serv' => 'réserv',
            'â€”' => '—',
            'â€“' => '—',
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ã´' => 'ô',
            'Ã' => 'à',
        ];
        
        $newContent = strtr($content, $replacements);
        
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            echo $file . " fixed.\n";
        }
    }
}
