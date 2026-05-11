<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class GeminiVoyageReportService
{
    private const GEMINI_MODEL = 'gemini-2.5-flash';
    private const DEFAULT_GEMINI_API_KEY = 'AIzaSyD_QHytuFMJCbFTPiM93SJ9S0J0VA59w84';

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{report: string, source: string, analysesCount: int, status: string}
     */
    public function generateFromSeasonalAnalyses(): array
    {
        $analyses = $this->fetchAnalyses();

        if ($analyses === []) {
            $analyses = $this->buildAnalysesFromVoyages((int) date('Y'));
        }

        if ($analyses === []) {
            return [
                'report' => "⚠️ Aucune analyse disponible.\nVeuillez d'abord générer les analyses saisonnières.",
                'source' => 'none',
                'analysesCount' => 0,
                'status' => 'empty',
            ];
        }

        $prompt = $this->buildPrompt($analyses);
        $apiKey = (string) ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? self::DEFAULT_GEMINI_API_KEY);

        if ($apiKey === '') {
            return [
                'report' => $this->buildFallbackReport($analyses) . "\n\n⚠️ Clé Gemini manquante (GEMINI_API_KEY).",
                'source' => 'fallback',
                'analysesCount' => count($analyses),
                'status' => 'no_api_key',
            ];
        }

        try {
            $report = $this->callGemini($prompt, $apiKey);

            return [
                'report' => $this->finalizeReport($report),
                'source' => 'gemini',
                'analysesCount' => count($analyses),
                'status' => 'ok',
            ];
        } catch (\Throwable $e) {
            return [
                'report' => $this->buildFallbackReport($analyses) . "\n\n❌ Erreur Gemini: " . $e->getMessage(),
                'source' => 'fallback',
                'analysesCount' => count($analyses),
                'status' => 'error',
            ];
        }
    }

    /**
     * @return list<array{saison:string,nombre_voyages:int,budget_moyen:float,duree_moyenne:int,type_voyage_dominant:string,preference_dominante:string}>
     */
    private function fetchAnalyses(): array
    {
        $sqlSnakeCase = <<<'SQL'
SELECT
    saison,
    nombre_voyages,
    budget_moyen,
    duree_moyenne,
    type_voyage_dominant,
    preference_dominante
FROM analyse_saisonniere
ORDER BY annee DESC, date_analyse DESC
SQL;

        $sqlCamelCase = <<<'SQL'
SELECT
    saison,
    nombreVoyages AS nombre_voyages,
    budgetMoyen AS budget_moyen,
    dureeMoyenne AS duree_moyenne,
    typeVoyageDominant AS type_voyage_dominant,
    preferenceDominante AS preference_dominante
FROM analyse_saisonniere
ORDER BY annee DESC, dateAnalyse DESC
SQL;

        try {
            return $this->connection->fetchAllAssociative($sqlSnakeCase);
        } catch (\Throwable) {
            try {
                return $this->connection->fetchAllAssociative($sqlCamelCase);
            } catch (\Throwable) {
                return []; // Table likely missing, fallback to building from voyages
            }
        }
    }

    /**
     * Reproduces JavaFX seasonal-analysis concept when no persisted analyses exist.
     *
     * @return list<array{saison:string,nombre_voyages:int,budget_moyen:float,duree_moyenne:int,type_voyage_dominant:string,preference_dominante:string}>
     */
    private function buildAnalysesFromVoyages(int $year): array
    {
        $voyages = $this->fetchVoyagesForYear($year);
        if ($voyages === []) {
            return [];
        }

        $grouped = [
            'Hiver' => [],
            'Printemps' => [],
            'Ete' => [],
            'Automne' => [],
        ];

        foreach ($voyages as $voyage) {
            $month = (int) ($voyage['month_num'] ?? 0);
            $season = $this->detectSeason($month);
            if (isset($grouped[$season])) {
                $grouped[$season][] = $voyage;
            }
        }

        $analyses = [];
        foreach ($grouped as $season => $seasonVoyages) {
            if ($seasonVoyages === []) {
                continue;
            }

            $count = count($seasonVoyages);
            $budgetSum = 0.0;
            $durationSum = 0;
            $types = [];

            foreach ($seasonVoyages as $item) {
                $budget = (float) ($item['prix_unitaire'] ?? 0);
                $duration = (int) ($item['duree_jours'] ?? 0);

                $budgetSum += $budget;
                $durationSum += $duration;

                $type = $this->detectTypeByBudget($budget);
                $types[$type] = ($types[$type] ?? 0) + 1;
            }

            arsort($types);
            $dominantType = (string) array_key_first($types);
            $averageBudget = $budgetSum / $count;
            $averageDuration = (int) floor($durationSum / $count);

            $preference = 'Court sejour';
            if ($averageBudget > 2000) {
                $preference = 'Budget eleve';
            } elseif ($averageDuration > 10) {
                $preference = 'Longue duree';
            }

            $analyses[] = [
                'saison' => $season,
                'nombre_voyages' => $count,
                'budget_moyen' => $averageBudget,
                'duree_moyenne' => $averageDuration,
                'type_voyage_dominant' => $dominantType,
                'preference_dominante' => $preference,
            ];
        }

        return $analyses;
    }

    /**
     * @return list<array{month_num:int,prix_unitaire:float,duree_jours:int}>
     */
    private function fetchVoyagesForYear(int $year): array
    {
        $sql = <<<'SQL'
SELECT
    MONTH(dateDepart) AS month_num,
    prix_unitaire,
    duree_jours
FROM voyage
WHERE dateDepart IS NOT NULL AND YEAR(dateDepart) = :year
SQL;

        return $this->connection->fetchAllAssociative($sql, ['year' => $year]);
    }

    private function detectSeason(int $month): string
    {
        if (in_array($month, [12, 1, 2], true)) {
            return 'Hiver';
        }
        if (in_array($month, [3, 4, 5], true)) {
            return 'Printemps';
        }
        if (in_array($month, [6, 7, 8], true)) {
            return 'Ete';
        }
        return 'Automne';
    }

    private function detectTypeByBudget(float $budget): string
    {
        if ($budget < 500) {
            return 'Economique';
        }
        if ($budget < 1500) {
            return 'Standard';
        }
        if ($budget < 3000) {
            return 'Confort';
        }
        return 'Luxe';
    }

    /**
     * @param list<array<string, mixed>> $analyses
     */
    private function buildPrompt(array $analyses): string
    {
        $reportDate = $this->currentDate();
        $resume = [];
        foreach ($analyses as $a) {
            $resume[] = sprintf(
                '- Saison : %s | Nombre de voyages : %d | Budget moyen : %.2f EUR | Duree moyenne : %d jours | Type dominant : %s | Preference : %s',
                (string) ($a['saison'] ?? 'N/A'),
                (int) ($a['nombre_voyages'] ?? 0),
                (float) ($a['budget_moyen'] ?? 0),
                (int) ($a['duree_moyenne'] ?? 0),
                (string) ($a['type_voyage_dominant'] ?? 'N/A'),
                (string) ($a['preference_dominante'] ?? 'N/A')
            );
        }

        return "Tu es un expert en tourisme et gestion d'agences de voyages.\n"
            . "Voici les analyses saisonnieres d'une agence de voyages :\n\n"
            . implode("\n", $resume)
            . "\n\n"
            . "Contexte obligatoire:\n"
            . "- Le nom de l'agence est Explora.\n"
            . "- La date du rapport est " . $reportDate . ".\n"
            . "- N'utilise aucun placeholder comme [Nom de l'Agence] ou [Date actuelle].\n"
            . "- Ecris explicitement en tete:\n"
            . "  Agence de Voyages : Explora\n"
            . "  Date : " . $reportDate . "\n\n"
            . "En te basant sur ces donnees, genere un rapport professionnel en francais avec :\n\n"
            . "1. SYNTHSE GLOBALE\n"
            . "   - Resume des tendances observees\n"
            . "   - Saison la plus rentable et la moins active\n\n"
            . "2. SUGGESTIONS PAR SAISON\n"
            . "   - Pour chaque saison, donne 2-3 recommandations concretes\n"
            . "   - Ex: types de voyages a promouvoir, destinations adaptees, offres speciales\n\n"
            . "3. STRATEGIE COMMERCIALE\n"
            . "   - 3 actions prioritaires pour ameliorer les ventes\n"
            . "   - Comment mieux cibler les voyageurs selon leurs preferences\n\n"
            . "4. PLAN D'ACTION\n"
            . "   - Recommandations pour les prochains mois\n\n"
            . "Le rapport doit etre clair, concis et directement actionnable pour une agence de voyages.";
    }

    private function finalizeReport(string $report): string
    {
        $date = $this->currentDate();
        $cleaned = str_replace(
            ['[Nom de l\'Agence]', '[Date actuelle]', '[Date du rapport]'],
            ['Explora', $date, $date],
            $report
        );

        $cleaned = preg_replace('/\*\*Agence de Voyages\s*:\s*\[[^\]]+\]\*\*/u', '**Agence de Voyages : Explora**', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\*\*Date\s*:\s*\[[^\]]+\]\*\*/u', '**Date : ' . $date . '**', $cleaned) ?? $cleaned;

        return $cleaned;
    }

    private function currentDate(): string
    {
        return date('d/m/Y');
    }

    private function callGemini(string $prompt, string $apiKey): string
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            rawurlencode($apiKey)
        );

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        // Bypass SSL verification for local dev environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $responseBody = curl_exec($ch);
        
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Impossible de joindre Gemini API. Erreur locale : ' . $error);
        }
        
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            throw new \RuntimeException('Erreur API Gemini (' . $statusCode . '): ' . $responseBody);
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Reponse Gemini invalide.');
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new \RuntimeException('Texte Gemini absent dans la reponse.');
        }

        return $text;
    }

    /**
     * @param list<array<string, mixed>> $analyses
     */
    private function buildFallbackReport(array $analyses): string
    {
        $count = count($analyses);
        $max = null;
        $min = null;

        foreach ($analyses as $analysis) {
            if ($max === null || (int) $analysis['nombre_voyages'] > (int) $max['nombre_voyages']) {
                $max = $analysis;
            }
            if ($min === null || (int) $analysis['nombre_voyages'] < (int) $min['nombre_voyages']) {
                $min = $analysis;
            }
        }

        $lines = [];
        $lines[] = 'Rapport IA (mode local)';
        $lines[] = 'Agence de Voyages : Explora';
        $lines[] = 'Date : ' . $this->currentDate();
        $lines[] = '----------------------------------------';
        $lines[] = '1) Synthese globale';
        $lines[] = 'Analyses disponibles: ' . $count;
        if ($max !== null && $min !== null) {
            $lines[] = 'Saison la plus active: ' . $max['saison'] . ' (' . $max['nombre_voyages'] . ' voyages)';
            $lines[] = 'Saison la moins active: ' . $min['saison'] . ' (' . $min['nombre_voyages'] . ' voyages)';
        }
        $lines[] = '';
        $lines[] = '2) Suggestions par saison';

        foreach ($analyses as $analysis) {
            $lines[] = '- ' . $analysis['saison'] . ': promouvoir les offres ' . $analysis['type_voyage_dominant']
                . ', budget moyen cible ' . number_format((float) $analysis['budget_moyen'], 2, '.', ' ') . ' EUR'
                . ', preference dominante: ' . $analysis['preference_dominante'] . '.';
        }

        $lines[] = '';
        $lines[] = '3) Strategie commerciale';
        $lines[] = '- Prioriser les campagnes de la saison la plus rentable.';
        $lines[] = '- Construire des bundles pour la saison la moins active.';
        $lines[] = '- Segmenter les offres selon les budgets moyens observes.';
        $lines[] = '';
        $lines[] = '4) Plan d action';
        $lines[] = '- M+1: lancer offre early-booking pour la prochaine saison.';
        $lines[] = '- M+2: A/B test de promotions par type de voyage dominant.';
        $lines[] = '- M+3: reevaluer performances et ajuster ciblage.';

        return implode("\n", $lines);
    }
}
