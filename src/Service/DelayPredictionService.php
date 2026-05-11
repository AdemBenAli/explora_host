<?php

namespace App\Service;

use App\Entity\Transport;

/**
 * 🤖 SERVICE DE PRÉDICTION DE RETARD (VERSION SIMPLIFIÉE)
 * Analyse les heures de pointes et les données de trafic IA
 */
class DelayPredictionService
{
    /**
     * 🎯 ESTIMER LA PROBABILITÉ DE RETARD
     */
    public function predictDelay(Transport $transport): array
    {
        $prob = 5; // Base: 5% (imprévus de base)
        $label = "À l'heure";
        $color = "#2ecc71"; // Vert
        $icon = "✅";
        $sources = [];

        // 1️⃣ ANALYSE IA (PHOTO DE TRAFIC OU ÉTAT MODIFIÉ)
        // L'impact s'applique si l'état n'est pas fluide (détecté par IA ou manuel)
        $etatTrafic = $transport->getEtatTrafic();
        $nomImage = $transport->getImageTraficUrl();

        if ($etatTrafic !== 'FLUIDE') {
            $impact = match ($etatTrafic) {
                'EMBOUTEILLAGE' => 60,
                'DENSE' => 35,
                'MODERE' => 15,
                default => 0
            };
            
            if ($impact > 0) {
                $prob += $impact;
                $sources[] = "Analyse Trafic (" . $etatTrafic . ")";
            }
        } elseif ($nomImage !== null && $nomImage !== '') {
            // Si c'est fluide et qu'il y a une image confirmant la fluidité
            $prob -= 5;
            $sources[] = "Trafic Fluide (Confirmé)";
        }

        // 2️⃣ ANALYSE DES HEURES DE POINTE (Rush Hours) & WEEKEND
        $heureDep = $transport->getHeureDepart();
        $dateDep = $transport->getDateDepart();

        if ($heureDep && $dateDep) {
            $H = (int) $heureDep->format('H');
            $M = (int) $heureDep->format('i');
            $totalMinutes = $H * 60 + $M;
            
            // On vérifie si c'est un jour de weekend (Samedi-Dimanche)
            $isWeekend = in_array((int)$dateDep->format('N'), [6, 7]);
            
            // Le trafic de week-end impacte TOUS les secteurs (Aéroports très fréquentés, etc.)
            if ($isWeekend) {
                $prob += 15;
                $sources[] = "Trafic de Week-end";
            }

            // Les embouteillages d'Heures de Pointe ne touchent QUE le secteur terrestre
            $type = $transport->getType()->name;
            $isTerrestre = in_array($type, ['TRAIN', 'BUS', 'TAXI', 'VOITURE']);

            if ($isTerrestre) {
                // 07:30 - 09:00 -> 450 à 540 min
                // 17:00 - 19:00 -> 1020 à 1140 min
                $rushMatin = ($totalMinutes >= 450 && $totalMinutes <= 540);
                $rushSoir  = ($totalMinutes >= 1020 && $totalMinutes <= 1140);

                if ($rushMatin || $rushSoir) {
                    $impactRush = $isWeekend ? 10 : 25; // L'impact est un peu lissé le weekend
                    $prob += $impactRush;
                    $sources[] = "Heure de pointe (Terrestre)";
                }
            }
        }

        // 3️⃣ FINALISATION (Min 2%, Max 99%)
        $prob = max(2, min(99, $prob));

        // Ajustement des seuils pour éviter d'être trop alarmiste si c'est seulement 15-20%
        if ($prob >= 75) {
            $label = "Retard Probable";
            $color = "#e74c3c"; 
            $icon = "🔴";
        } elseif ($prob >= 45) {
            $label = "Risque de Retard";
            $color = "#f39c12"; 
            $icon = "🟠";
        } elseif ($prob >= 25) {
            $label = "Léger Retard";
            $color = "#f1c40f"; 
            $icon = "🟡";
        }

        return [
            'probability' => $prob,
            'label' => $prob < 25 ? "À l'heure" : $label,
            'color' => $prob < 25 ? "#2ecc71" : $color,
            'icon'  => $prob < 25 ? "✅" : $icon,
            'justification' => empty($sources) ? "Conditions idéales" : implode(" + ", $sources)
        ];
    }
}
