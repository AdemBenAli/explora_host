<?php
namespace App\Controller\Agent;

use App\Repository\ActiviteRepository;
use App\Repository\AvisActiviteRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/agent/dashboard', name: 'agent_dashboard')]
class AgentDashboardController extends AbstractController
{
    private string $geminiKey = 'AIzaSyCf27NUT16mbYgfthqluyQJIv8bkpndfz4';

    private function getAgent(Request $request, UtilisateurRepository $repo): mixed
    {
        $id   = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$id || $role !== 'AGENT') return null;
        return $repo->find($id);
    }

    private function callGemini(string $prompt): string
    {
        $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key='.$this->geminiKey;
        $body = json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['temperature'=>0.6,'maxOutputTokens'=>600]]);
        $ch   = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_TIMEOUT=>20]);
        $raw  = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($code !== 200) return 'Analyse indisponible.';
        $decoded = json_decode($raw, true);
        return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? 'Analyse indisponible.';
    }

    private function collectAgentData(int $agentId, ActiviteRepository $actRepo, AvisActiviteRepository $avisRepo): array
    {
        $activites  = $actRepo->findByAgentSimple($agentId);
        $voyPerAct  = $actRepo->findVoyageCountForAgent($agentId);
        $notePerAct = $avisRepo->getAverageByActivite();
        $noteMoy    = $avisRepo->getNoteMoyenneAgent($agentId);

        // category breakdown
        $catCount = [];
        foreach ($activites as $a) $catCount[$a->getCategorie()] = ($catCount[$a->getCategorie()] ?? 0) + 1;
        arsort($catCount);

        // top activités by voyage
        arsort($voyPerAct);
        $actMap = [];
        foreach ($activites as $a) $actMap[$a->getIdActivite()] = $a;

        $topActs = [];
        $maxV = 1;
        foreach (array_slice($voyPerAct, 0, 6, true) as $id => $cnt) {
            $act = $actMap[$id] ?? null;
            $note = $notePerAct[$id] ?? 0;
            $topActs[] = ['nom'=>$act?->getNom()??'#'.$id,'ville'=>$act?->getVille()??'','count'=>$cnt,'note'=>$note,'dispo'=>$act?->isDisponible()??false];
            $maxV = max($maxV, $cnt);
        }

        // performance table (all activités with their note and voyage count)
        $perf = [];
        foreach ($activites as $a) {
            $id = $a->getIdActivite();
            $perf[] = ['nom'=>$a->getNom(),'ville'=>$a->getVille(),'count'=>$voyPerAct[$id]??0,
                'note'=>$notePerAct[$id]??0,'dispo'=>$a->isDisponible()];
        }
        usort($perf, fn($a,$b) => $b['note'] <=> $a['note']);
        $perf = array_slice($perf, 0, 8);

        // recent avis
        $ids = array_map(fn($a) => $a->getIdActivite(), $activites);
        $recentAvis = $avisRepo->findRecentByActiviteIds($ids, 5);

        return compact('activites','catCount','topActs','maxV','perf','recentAvis','noteMoy','actMap','notePerAct','voyPerAct');
    }

    // ── INDEX ──────────────────────────────────────────────────────────
    #[Route('', name: '')]
    public function index(Request $request, ActiviteRepository $actRepo, AvisActiviteRepository $avisRepo,
                          UtilisateurRepository $userRepo): Response
    {
        $agent = $this->getAgent($request, $userRepo);
        if (!$agent) return $this->redirectToRoute('app_login');

        $d = $this->collectAgentData($agent->getId(), $actRepo, $avisRepo);

        return $this->render('dashboard/agent_dashboard.html.twig', [
            'agent'          => $agent,
            'totalActivites' => count($d['activites']),
            'disponibles'    => count(array_filter($d['activites'], fn($a) => $a->isDisponible())),
            'totalVoyages'   => array_sum($d['voyPerAct']),
            'totalAvis'      => array_sum(array_map(fn($a) => 1, $d['recentAvis'])),
            'noteMoyenne'    => $d['noteMoy'],
            'catCount'       => $d['catCount'],
            'totalForCat'    => max(1, count($d['activites'])),
            'topActs'        => $d['topActs'],
            'maxVoy'         => $d['maxV'],
            'perf'           => $d['perf'],
            'recentAvis'     => $d['recentAvis'],
            'actMap'         => $d['actMap'],
        ]);
    }

    // ── RAPPORT PDF ────────────────────────────────────────────────────
    #[Route('/rapport', name: '_rapport', methods: ['POST'])]
    public function rapport(Request $request, ActiviteRepository $actRepo, AvisActiviteRepository $avisRepo,
                            UtilisateurRepository $userRepo): Response
    {
        $agent = $this->getAgent($request, $userRepo);
        if (!$agent) return $this->redirectToRoute('app_login');

        $d = $this->collectAgentData($agent->getId(), $actRepo, $avisRepo);
        $agentNom = $agent->getNom().' '.$agent->getPrenom();

        $prompt  = "Tu es expert en analyse de performance pour agents touristiques en Tunisie.\n";
        $prompt .= "RAPPORT INDIVIDUEL — Agent : $agentNom | ".date('d/m/Y')."\n";
        $prompt .= "Activités : ".count($d['activites'])." | Disponibles : ".count(array_filter($d['activites'],fn($a)=>$a->isDisponible()))."\n";
        $prompt .= "Voyages : ".array_sum($d['voyPerAct'])." | Note moy : ".$d['noteMoy']."/5\n";
        $prompt .= "TOP ACTIVITES :\n";
        foreach (array_slice($d['topActs'],0,5) as $i=>$ac) {
            $prompt .= ($i+1).". ".$ac['nom']." — ".$ac['count']." voyages, note: ".($ac['note']?:'-')."\n";
        }
        $prompt .= "CATEGORIES : "; foreach ($d['catCount'] as $c=>$n) $prompt .= "$c:$n ";
        $prompt .= "\nRédige un rapport personnalisé (max 300 mots) : 1.Résumé 2.Points forts 3.Activités attractives 4.Satisfaction client 5.Recommandations.";

        $analyse = $this->callGemini($prompt);

        $pdf = $this->buildAgentPdf($agentNom, $d, $analyse);

        $filename = 'rapport_agent_'.strtolower(preg_replace('/\s+/','_',$agent->getNom())).'_'.date('Y-m-d').'.pdf';
        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length'      => strlen($pdf),
        ]);
    }

    private function buildAgentPdf(string $agentNom, array $d, string $analyse): string
    {
        $W=794; $H=1123;

        // PAGE 1
        $img = imagecreatetruecolor($W,$H);
        $white  = imagecolorallocate($img,255,255,255);
        $orange = imagecolorallocate($img,243,156,18);
        $dark   = imagecolorallocate($img,26,34,53);
        $lgray  = imagecolorallocate($img,240,243,248);
        imagefilledrectangle($img,0,0,$W,$H,$white);

        imagefilledrectangle($img,0,0,$W,90,$orange);
        $this->imgTC($img,'EXPLORA — Mon Rapport Agent',$W,36,5,[255,255,255],$img);
        $this->imgTC($img,'Agent : '.$agentNom.' | Propulsé par Gemini AI',$W,62,3,[255,240,200],$img);
        $this->imgTC($img,'Date : '.date('d/m/Y'),$W,80,2,[255,240,200],$img);

        $y=105;

        // stats
        $statCols=[[243,156,18],[39,174,96],[102,126,234],[231,76,60],[26,188,156]];
        $disponibles=count(array_filter($d['activites'],fn($a)=>$a->isDisponible()));
        $totalVoy=array_sum($d['voyPerAct']);
        $statVals=[count($d['activites']),$disponibles,$totalVoy,count($d['recentAvis']),$d['noteMoy']?:'-'];
        $statLbls=['Activités','Disponibles','Voyages','Avis reçus','Note moy.'];
        $bw=($W-80)/5;
        foreach($statVals as $i=>$val){
            $bx=(int)(40+$i*$bw);
            $sc=imagecolorallocate($img,...$statCols[$i]);
            imagefilledrectangle($img,$bx,$y,(int)($bx+$bw-6),$y+50,$sc);
            $this->imgTC($img,(string)$val,(int)($bw-6),$y+28,4,[255,255,255],$img,$bx);
            $this->imgTC($img,$statLbls[$i],(int)($bw-6),$y+44,1,[220,220,220],$img,$bx);
        }
        $y+=62;

        // top activités
        $y=$this->sec($img,$y,$W,'Mes activités les plus populaires',$orange,$dark);
        $y=$this->th($img,$y,$W,['#','Activité','Ville','Voyages','Note'],[26,170,90,60,60],$orange);
        foreach(array_slice($d['topActs'],0,6) as $i=>$ac){
            $note=$ac['note']?round($ac['note'],1).'/5':'—';
            $y=$this->tr($img,$y,$W,[($i+1).'.',$ac['nom'],$ac['ville'],(string)$ac['count'],$note],[26,170,90,60,60],$i%2==1,$dark,$lgray,$white);
        }
        $y+=8;

        // categories
        $y=$this->sec($img,$y,$W,'Répartition par catégorie',$orange,$dark);
        $y=$this->th($img,$y,$W,['Catégorie','Nb','%'],[220,60,80],$orange);
        $tot=max(1,count($d['activites']));
        foreach($d['catCount'] as $cat=>$cnt){
            $ci=array_search($cat,array_keys($d['catCount']));
            $y=$this->tr($img,$y,$W,[$cat,(string)$cnt,round($cnt*100/$tot,1).'%'],[220,60,80],$ci%2==1,$dark,$lgray,$white);
        }
        $y+=8;

        // performance table
        $y=$this->sec($img,$y,$W,'Performance de mes activités',$orange,$dark);
        $y=$this->th($img,$y,$W,['Activité','Voyages','Note','Statut'],[200,60,80,60],$orange);
        foreach($d['perf'] as $i=>$p){
            $note=$p['note']?round($p['note'],1).'/5':'—';
            $statut=$p['dispo']?'Dispo':'Inactif';
            $y=$this->tr($img,$y,$W,[$p['nom'],(string)$p['count'],$note,$statut],[200,60,80,60],$i%2==1,$dark,$lgray,$white);
        }

        // footer
        imagefilledrectangle($img,0,$H-44,$W,$H,$lgray);
        imagefilledrectangle($img,0,$H-44,$W,$H-42,$orange);
        $this->imgTC($img,'Rapport Agent Explora | '.$agentNom.' | Gemini AI | '.date('d/m/Y'),$W,$H-18,1,[100,100,100],$img);

        ob_start(); imagejpeg($img,null,90); $jpg1=ob_get_clean(); imagedestroy($img);

        // PAGE 2: analyse
        $img2=imagecreatetruecolor($W,$H);
        $white2=imagecolorallocate($img2,255,255,255);
        $orange2=imagecolorallocate($img2,243,156,18);
        $dark2=imagecolorallocate($img2,26,34,53);
        $lgray2=imagecolorallocate($img2,240,243,248);
        imagefilledrectangle($img2,0,0,$W,$H,$white2);

        $py=40;
        $py=$this->sec($img2,$py,$W,'Analyse Personnalisée — Intelligence Artificielle',$orange2,$dark2);

        $clean=preg_replace('/\*+/','',strip_tags($analyse));
        foreach(explode("\n",$clean) as $line){
            $line=trim($line); if(!$line){$py+=6;continue;}
            if(preg_match('/^[0-9]+\./',$line)||str_starts_with($line,'#')){
                $py+=4;
                imagestring($img2,4,40,$py-12,substr($line,0,100),$orange2);
                $py+=18;
            } else {
                $py=$this->wrap($img2,$line,40,$py,$W-80,$dark2);
            }
            if($py>$H-60) break;
        }

        imagefilledrectangle($img2,0,$H-44,$W,$H,$lgray2);
        imagefilledrectangle($img2,0,$H-44,$W,$H-42,$orange2);
        $this->imgTC($img2,'Rapport Agent | '.$agentNom.' | Gemini AI | '.date('d/m/Y'),$W,$H-18,1,[100,100,100],$img2);

        ob_start(); imagejpeg($img2,null,90); $jpg2=ob_get_clean(); imagedestroy($img2);

        return $this->pdfBytes([$jpg1,$jpg2],$W,$H);
    }

    // ── helpers ──────────────────────────────────────────────────────────
    private function imgTC($img,string $t,int $w,int $y,int $f,array $rgb,$ref,int $xb=0):void{
        $c=imagecolorallocate($ref,...$rgb);
        $tw=imagefontwidth($f)*strlen($t);
        imagestring($ref,$f,$xb+($w-$tw)/2,$y-imagefontheight($f),$t,$c);
    }
    private function sec($img,int $y,int $W,string $title,$accent,$dark):int{
        $y+=10; imagestring($img,4,40,$y-12,$title,$dark);
        imagefilledrectangle($img,40,$y,$W-40,$y+2,$accent); return $y+12;
    }
    private function th($img,int $y,int $W,array $cols,array $ws,$accent):int{
        $x=40;$rh=20;$wh=imagecolorallocate($img,255,255,255);
        foreach($cols as $i=>$c){
            imagefilledrectangle($img,$x,$y,$x+$ws[$i]-2,$y+$rh,$accent);
            $tw=imagefontwidth(2)*strlen($c);
            imagestring($img,2,$x+($ws[$i]-$tw)/2,$y+4,$c,$wh);
            $x+=$ws[$i];
        }
        return $y+$rh;
    }
    private function tr($img,int $y,int $W,array $vals,array $ws,bool $alt,$dark,$lgray,$white):int{
        $x=40;$rh=17;
        imagefilledrectangle($img,40,$y,40+array_sum($ws),$y+$rh,$alt?$lgray:$white);
        foreach($vals as $i=>$v){imagestring($img,2,$x+3,$y+3,substr((string)$v,0,38),$dark);$x+=$ws[$i];}
        return $y+$rh;
    }
    private function wrap($img,string $text,int $x,int $y,int $maxW,$color):int{
        $words=explode(' ',$text);$line='';$lh=14;
        foreach($words as $word){
            $test=$line===''?$word:$line.' '.$word;
            if(imagefontwidth(2)*strlen($test)>$maxW){imagestring($img,2,$x,$y-12,$line,$color);$y+=$lh;$line=$word;}
            else $line=$test;
        }
        if($line!==''){imagestring($img,2,$x,$y-12,$line,$color);$y+=$lh;}
        return $y;
    }
    private function pdfBytes(array $jpegs,int $W,int $H):string{
        $out='';$offset=0;$xref=[];
        $header="%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $out.=$header;$offset=strlen($header);
        $n=count($jpegs);
        $pObj=[];$cObj=[];$iObj=[];
        for($i=0;$i<$n;$i++){$pObj[$i]=3+$i*3;$cObj[$i]=4+$i*3;$iObj[$i]=5+$i*3;}
        $xref[]=0;$xref[]=$offset;
        $cat="1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $out.=$cat;$offset+=strlen($cat);
        $kids=implode(' ',array_map(fn($i)=>$pObj[$i].' 0 R',range(0,$n-1)));
        $xref[]=$offset;
        $pages="2 0 obj\n<< /Type /Pages /Kids [$kids] /Count $n >>\nendobj\n";
        $out.=$pages;$offset+=strlen($pages);
        foreach($jpegs as $i=>$jpg){
            $xref[]=$offset;
            $pg="{$pObj[$i]} 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 $W $H]\n/Contents {$cObj[$i]} 0 R\n/Resources << /XObject << /Img$i {$iObj[$i]} 0 R >> >>\n>>\nendobj\n";
            $out.=$pg;$offset+=strlen($pg);
            $sd="q\n$W 0 0 $H 0 0 cm\n/Img$i Do\nQ\n";
            $xref[]=$offset;
            $co="{$cObj[$i]} 0 obj\n<< /Length ".strlen($sd)." >>\nstream\n".$sd."\nendstream\nendobj\n";
            $out.=$co;$offset+=strlen($co);
            $xref[]=$offset;
            $ih="{$iObj[$i]} 0 obj\n<< /Type /XObject /Subtype /Image /Width $W /Height $H /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpg)." >>\nstream\n";
            $out.=$ih;$offset+=strlen($ih);
            $out.=$jpg;$offset+=strlen($jpg);
            $es="\nendstream\nendobj\n";$out.=$es;$offset+=strlen($es);
        }
        $xrefOffset=$offset;$total=2+$n*3;
        $out.="xref\n0 ".($total+1)."\n";
        $out.="0000000000 65535 f \n";
        for($i=1;$i<count($xref);$i++) $out.=sprintf("%010d 00000 n \n",$xref[$i]);
        for($i=count($xref);$i<=$total;$i++) $out.="0000000000 00000 n \n";
        $out.="trailer\n<< /Size ".($total+1)." /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";
        return $out;
    }
}
