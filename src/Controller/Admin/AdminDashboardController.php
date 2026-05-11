<?php

namespace App\Controller\Admin;

use App\Repository\ActiviteRepository;
use App\Repository\AvisActiviteRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dashboard', name: 'admin_dashboard')]
class AdminDashboardController extends AbstractController
{
    private string $geminiKey = 'AIzaSyCf27NUT16mbYgfthqluyQJIv8bkpndfz4';

    // ======================================================================
    // HELPERS
    // ======================================================================

    private function getAdmin(Request $request, UtilisateurRepository $repo): mixed
    {
        $id   = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$id || $role !== 'ADMIN') return null;
        return $repo->find($id);
    }

    private function callGemini(string $prompt): string
    {
        $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=' . $this->geminiKey;
        $body = json_encode([
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.6, 'maxOutputTokens' => 600],
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) return 'Analyse indisponible.';
        $d = json_decode($raw, true);
        return $d['candidates'][0]['content']['parts'][0]['text'] ?? 'Analyse indisponible.';
    }

    // ======================================================================
    // COLLECTE DES DONNEES
    // ======================================================================

    private function collectData(
        ActiviteRepository    $actRepo,
        AvisActiviteRepository $avisRepo,
        UtilisateurRepository  $userRepo,
        VoyageRepository       $voyRepo
    ): array {
        $activites  = $actRepo->findAllOrdered();
        $agents     = $userRepo->findAgents();
        $voyCount   = $voyRepo->countAll();
        $avisCount  = $avisRepo->countAll();
        $voyPerAct  = $actRepo->findVoyageCountPerActivite();
        $notePerAct = $avisRepo->getAverageByActivite();

        $agentMap = [];
        foreach ($agents as $ag) {
            $agentMap[$ag->getId()] = $ag->getNom() . ' ' . $ag->getPrenom();
        }

        $actMap = [];
        foreach ($activites as $a) {
            $actMap[$a->getIdActivite()] = $a;
        }

        // Top agents par activites
        $agentActCount = [];
        foreach ($activites as $a) {
            if ($a->getIdAgent()) {
                $agentActCount[$a->getIdAgent()] = ($agentActCount[$a->getIdAgent()] ?? 0) + 1;
            }
        }
        arsort($agentActCount);

        $topAgentsAct = [];
        $maxAct = 1;
        foreach (array_slice($agentActCount, 0, 5, true) as $id => $cnt) {
            $topAgentsAct[] = ['nom' => $agentMap[$id] ?? 'Agent #' . $id, 'count' => $cnt];
            $maxAct = max($maxAct, $cnt);
        }

        // Categories
        $catCount = [];
        foreach ($activites as $a) {
            $cat = $a->getCategorie();
            if ($cat) {
                $catCount[(string)$cat] = ($catCount[(string)$cat] ?? 0) + 1;
            }
        }
        arsort($catCount);

        // Top activites par voyage
        arsort($voyPerAct);
        $topActs = [];
        $maxVoy  = 1;
        foreach (array_slice($voyPerAct, 0, 6, true) as $id => $cnt) {
            $act = $actMap[$id] ?? null;
            $nom = ($act?->getNom() ?? '#' . $id) . ($act?->getVille() ? ' - ' . $act->getVille() : '');
            $topActs[] = ['nom' => $nom, 'count' => $cnt];
            $maxVoy = max($maxVoy, $cnt);
        }

        // Top agents par note
        $notesByAgent = [];
        foreach ($notePerAct as $idAct => $note) {
            $act = $actMap[$idAct] ?? null;
            if ($act && $act->getIdAgent()) {
                $notesByAgent[$act->getIdAgent()][] = $note;
            }
        }
        $agentNotes = [];
        foreach ($notesByAgent as $agId => $notes) {
            $agentNotes[] = [
                'nom'  => $agentMap[$agId] ?? 'Agent #' . $agId,
                'note' => round(array_sum($notes) / count($notes), 1),
            ];
        }
        usort($agentNotes, fn($a, $b) => $b['note'] <=> $a['note']);
        $agentNotes = array_slice($agentNotes, 0, 5);

        $disponibles = count(array_filter($activites, fn($a) => $a->isDisponible()));

        return compact(
            'activites', 'agents', 'voyCount', 'avisCount', 'voyPerAct', 'notePerAct',
            'topAgentsAct', 'catCount', 'topActs', 'agentNotes', 'actMap',
            'maxAct', 'maxVoy', 'disponibles'
        );
    }

    // ======================================================================
    // ROUTES
    // ======================================================================

    #[Route('', name: '')]
    public function index(
        Request               $request,
        ActiviteRepository    $actRepo,
        AvisActiviteRepository $avisRepo,
        UtilisateurRepository  $userRepo,
        VoyageRepository       $voyRepo
    ): Response {
        if (!$this->getAdmin($request, $userRepo)) {
            return $this->redirectToRoute('app_login');
        }

        $data  = $this->collectData($actRepo, $avisRepo, $userRepo, $voyRepo);
        $admin = $this->getAdmin($request, $userRepo);

        return $this->render('dashboard/admin_dashboard.html.twig', [
            'admin'          => $admin,
            'totalActivites' => count($data['activites']),
            'totalAgents'    => count($data['agents']),
            'totalVoyages'   => $data['voyCount'],
            'totalAvis'      => $data['avisCount'],
            'disponibles'    => $data['disponibles'],
            'topAgentsAct'   => $data['topAgentsAct'],
            'maxAgentAct'    => $data['maxAct'],
            'catCount'       => $data['catCount'],
            'totalForCat'    => count($data['activites']),
            'topActs'        => $data['topActs'],
            'maxVoy'         => $data['maxVoy'],
            'agentNotes'     => $data['agentNotes'],
        ]);
    }

    #[Route('/rapport', name: '_rapport', methods: ['POST'])]
    public function rapport(
        Request               $request,
        ActiviteRepository    $actRepo,
        AvisActiviteRepository $avisRepo,
        UtilisateurRepository  $userRepo,
        VoyageRepository       $voyRepo
    ): Response {
        if (!$this->getAdmin($request, $userRepo)) {
            return $this->redirectToRoute('app_login');
        }

        $d = $this->collectData($actRepo, $avisRepo, $userRepo, $voyRepo);

        $prompt  = "Expert analyse plateforme tourisme Tunisie.\n";
        $prompt .= "DONNEES EXPLORA - " . date('d/m/Y') . "\n";
        $prompt .= "Activites:" . count($d['activites'])
            . "(dispo:" . $d['disponibles'] . ") | "
            . "Agents:"  . count($d['agents'])
            . " | Voyages:" . $d['voyCount']
            . " | Avis:"    . $d['avisCount'] . "\n";

        $prompt .= "TOP AGENTS (activites):\n";
        foreach ($d['topAgentsAct'] as $i => $ag) {
            $prompt .= ($i + 1) . ". " . $ag['nom'] . " - " . $ag['count'] . " activites\n";
        }
        $prompt .= "TOP AGENTS (satisfaction):\n";
        foreach (array_slice($d['agentNotes'], 0, 5) as $i => $an) {
            $prompt .= ($i + 1) . ". " . $an['nom'] . " - " . $an['note'] . "/5\n";
        }
        $prompt .= "TOP ACTIVITES:\n";
        foreach ($d['topActs'] as $i => $ac) {
            $prompt .= ($i + 1) . ". " . $ac['nom'] . " - " . $ac['count'] . " voyages\n";
        }
        $prompt .= "CATEGORIES: ";
        foreach ($d['catCount'] as $c => $n) {
            $prompt .= "$c:$n ";
        }
        $prompt .= "\nRedige: 1.Resume executif 2.Agents performants 3.Activites attractives "
            . "4.Points forts 5.Recommandations. Francais, max 300 mots.";

        $analyse = $this->callGemini($prompt);
        $pdf     = $this->generatePdf($d, $analyse);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport_explora_' . date('Y-m-d') . '.pdf"',
            'Content-Length'      => strlen($pdf),
        ]);
    }

    // ======================================================================
    // GENERATION PDF via TCPDF (tecnickcom/tcpdf)
    // ======================================================================

    private function generatePdf(array $d, string $analyse): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Explora Admin');
        $pdf->SetAuthor('Explora');
        $pdf->SetTitle('Rapport Analytique Explora');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->AddPage();
        $this->pdfPage1($pdf, $d);

        $pdf->AddPage();
        $this->pdfPage2($pdf, $analyse);

        return $pdf->Output('rapport.pdf', 'S');
    }

    private function pdfBanner(\TCPDF $pdf, string $sub): void
    {
        $pdf->SetFillColor(102, 126, 234);
        $pdf->Rect(0, 0, 210, 28, 'F');
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(0, 5);
        $pdf->Cell(210, 9, 'EXPLORA', 0, 1, 'C', false);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(200, 210, 255);
        $pdf->SetXY(0, 15);
        $pdf->Cell(210, 6, $sub, 0, 1, 'C', false);
        $pdf->SetXY(0, 21);
        $pdf->Cell(210, 5, 'Date : ' . date('d/m/Y'), 0, 1, 'C', false);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(8);
    }

    private function pdfSection(\TCPDF $pdf, string $title): void
    {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(26, 34, 53);
        $pdf->Cell(0, 7, $title, 0, 1, 'L');
        $pdf->SetFillColor(102, 126, 234);
        $pdf->Rect(15, $pdf->GetY(), 180, 0.5, 'F');
        $pdf->Ln(4);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function pdfTHead(\TCPDF $pdf, array $cols, array $ws): void
    {
        $pdf->SetFillColor(102, 126, 234);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        foreach ($cols as $i => $col) {
            $pdf->Cell($ws[$i], 7, $col, 0, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
    }

    private function pdfTRow(\TCPDF $pdf, array $vals, array $ws, bool $alt): void
    {
        $pdf->SetFillColor($alt ? 240 : 255, $alt ? 243 : 255, $alt ? 248 : 255);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($vals as $i => $v) {
            $pdf->Cell($ws[$i], 6, mb_substr((string)$v, 0, 50), 0, 0, 'L', true);
        }
        $pdf->Ln();
    }

    private function pdfFooter(\TCPDF $pdf): void
    {
        $pdf->SetY(-15);
        $pdf->SetFillColor(240, 243, 248);
        $pdf->Rect(0, $pdf->GetY() - 2, 210, 17, 'F');
        $pdf->SetFillColor(102, 126, 234);
        $pdf->Rect(0, $pdf->GetY() - 2, 210, 0.5, 'F');
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(127, 140, 141);
        $pdf->Cell(0, 10, 'Rapport Explora Admin | Google Gemini AI | ' . date('d/m/Y'), 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    private function pdfPage1(\TCPDF $pdf, array $d): void
    {
        $this->pdfBanner($pdf, 'Rapport Analytique | Propulse par IA Gemini');

        // Bandeaux stats
        $this->pdfSection($pdf, "Vue d'ensemble");
        $statColors = [[102,126,234],[26,188,156],[39,174,96],[246,168,33],[231,76,60]];
        $statVals   = [count($d['activites']),$d['disponibles'],count($d['agents']),$d['voyCount'],$d['avisCount']];
        $statLabels = ['Activites','Disponibles','Agents','Voyages','Avis'];
        $bw = 36; $x0 = 15; $y0 = $pdf->GetY();
        foreach ($statVals as $i => $val) {
            [$r,$g,$b] = $statColors[$i];
            $pdf->SetFillColor($r, $g, $b);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Rect($x0 + $i * $bw, $y0, $bw - 2, 18, 'F');
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetXY($x0 + $i * $bw, $y0 + 2);
            $pdf->Cell($bw - 2, 8, (string)$val, 0, 0, 'C');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY($x0 + $i * $bw, $y0 + 11);
            $pdf->Cell($bw - 2, 6, $statLabels[$i], 0, 0, 'C');
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(22);

        // Top agents activites
        $medals = ['1.','2.','3.','4.','5.'];
        $this->pdfSection($pdf, 'Top Agents - Activites creees');
        $this->pdfTHead($pdf, ['#', 'Agent', 'Nb'], [10, 140, 30]);
        foreach (array_slice($d['topAgentsAct'], 0, 5) as $i => $ag) {
            $this->pdfTRow($pdf, [$medals[$i] ?? ($i+1).'.', $ag['nom'], $ag['count']], [10, 140, 30], $i % 2 === 1);
        }
        $pdf->Ln(3);

        // Top activites
        $this->pdfSection($pdf, 'Activites les plus populaires (voyages associes)');
        $this->pdfTHead($pdf, ['#', 'Activite', 'Voyages'], [10, 150, 20]);
        foreach (array_slice($d['topActs'], 0, 6) as $i => $ac) {
            $this->pdfTRow($pdf, [($i+1).'.', $ac['nom'], $ac['count']], [10, 150, 20], $i % 2 === 1);
        }
        $pdf->Ln(3);

        // Categories
        $this->pdfSection($pdf, 'Repartition par categorie');
        $this->pdfTHead($pdf, ['Categorie', 'Nb', '%'], [110, 20, 30]);
        $tot = max(1, count($d['activites']));
        $ci  = 0;
        foreach ($d['catCount'] as $cat => $cnt) {
            $pct = round($cnt * 100 / $tot, 1);
            $this->pdfTRow($pdf, [$cat, $cnt, $pct . '%'], [110, 20, 30], $ci++ % 2 === 1);
        }
        $pdf->Ln(3);

        // Notes agents
        $this->pdfSection($pdf, 'Top Agents - Satisfaction client (note /5)');
        $this->pdfTHead($pdf, ['#', 'Agent', 'Note', 'Etoiles'], [10, 110, 25, 35]);
        foreach (array_slice($d['agentNotes'], 0, 5) as $i => $an) {
            $r     = (int)round($an['note']);
            $stars = str_repeat('*', $r) . str_repeat('-', 5 - $r);
            $this->pdfTRow($pdf, [$medals[$i] ?? ($i+1).'.', $an['nom'], number_format($an['note'], 1).'/5', $stars], [10, 110, 25, 35], $i % 2 === 1);
        }

        $this->pdfFooter($pdf);
    }

    private function pdfPage2(\TCPDF $pdf, string $analyse): void
    {
        $this->pdfBanner($pdf, 'Analyse & Recommandations - Intelligence Artificielle');
        $this->pdfSection($pdf, 'Analyse Gemini AI');

        $clean = preg_replace('/\*+/', '', strip_tags($analyse));
        $lines = explode("\n", $clean);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { $pdf->Ln(2); continue; }

            if (preg_match('/^[0-9]+\./', $line) || str_starts_with($line, '#')) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetTextColor(102, 126, 234);
                $pdf->Ln(2);
                $pdf->MultiCell(0, 6, ltrim($line, '#0123456789. '), 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            } elseif (str_starts_with($line, '-') || str_starts_with($line, "\xe2\x80\xa2")) {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetX(20);
                $pdf->MultiCell(170, 5, '- ' . ltrim($line, '-'), 0, 'L');
            } else {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->MultiCell(0, 5, $line, 0, 'L');
            }
        }

        $this->pdfFooter($pdf);
    }
}
