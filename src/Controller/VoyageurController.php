<?php

namespace App\Controller;
    use App\Repository\AvisActiviteRepository;

use App\Entity\Planning;
use App\Repository\ActiviteRepository;
use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\VoyageRepository;

#[Route('/voyageur', name: 'voyageur_')]
class VoyageurController extends AbstractController
{
    // ── Récupère le voyageur connecté depuis la session ──────────────────
    private function getVoyageur(Request $request, UtilisateurRepository $userRepo): mixed
    {
        $id   = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');

        if (!$id || strtolower($role) !== 'voyageur') {
            return null;
        }
        return $userRepo->find($id);
    }

    // ── INDEX : Liste des activités disponibles ──────────────────────────
    #[Route('', name: 'index')]
public function index(
    Request               $request,
    ActiviteRepository    $activiteRepo,
    UtilisateurRepository $userRepo,
    PlanningRepository    $planningRepo,
    VoyageRepository      $voyageRepo
): Response {
    $voyageur = $this->getVoyageur($request, $userRepo);
    if (!$voyageur) {
        return $this->redirectToRoute('app_login');
    }

    $activites = $activiteRepo->findDisponibles();

    // Récupérer les IDs des activités déjà dans le planning
    $plannings = $planningRepo->findByVoyageur($voyageur->getId());
    $activitesEnPlanning = array_map(
        fn($p) => $p->getActivite()->getIdActivite(),
        $plannings
    );

    $voyages = $voyageRepo->findAll();

    return $this->render('/activite/voyageur/index.html.twig', [
        'activites'           => $activites,
        'voyages'             => $voyages,
        'voyageur'            => $voyageur,
        'activitesEnPlanning' => $activitesEnPlanning,  // ← nouveau
    ]);
}

    // ── METEO : Endpoint AJAX pour récupérer la météo d'une activité ─────
    #[Route('/meteo/{id}', name: 'meteo', methods: ['GET'])]
    public function meteo(
        int $id,
        ActiviteRepository $activiteRepo,
        \App\Service\MeteoService $meteoService
    ): Response {
        $activite = $activiteRepo->find($id);
        if (!$activite) {
            return $this->json(['error' => 'Activité introuvable'], 404);
        }

        $meteo = $meteoService->getMeteo($activite->getVille(), $activite->getDateActivite());
        if (!$meteo) {
            return $this->json(['error' => 'Météo indisponible'], 404);
        }

        return $this->json($meteo);
    }

    // ── DÉTAIL d'une activité ────────────────────────────────────────────

#[Route('/activite/{id}', name: 'activite_detail')]
public function detail(
    int $id,
    Request $request,
    ActiviteRepository $activiteRepo,
    UtilisateurRepository $userRepo,
    AvisActiviteRepository $avisRepo
): Response {

    $voyageur = $this->getVoyageur($request, $userRepo);
    if (!$voyageur) {
        return $this->redirectToRoute('app_login');
    }

    $activite = $activiteRepo->find($id);
    if (!$activite) {
        $this->addFlash('error', 'Activité introuvable.');
        return $this->redirectToRoute('voyageur_index');
    }

    // ✅ récupérer les avis
    $avis = $avisRepo->findByActivite($id);

    // ✅ avis du voyageur connecté
    $monAvis = $avisRepo->findOneBy([
        'activite' => $activite,
        'idVoyageur' => $voyageur->getId(),
    ]);

    // ✅ calcul moyenne + nombre
    $nbAvis = count($avis);
    $total = 0;

    foreach ($avis as $a) {
        $total += $a->getNote(); // ou getEtoiles selon ton entity
    }

    $moyenne = $nbAvis > 0 ? $total / $nbAvis : 0;

    return $this->render('activite/voyageur/detail_activite.html.twig', [
        'activite' => $activite,
        'voyageur' => $voyageur,
        'avis' => $avis,
        'monAvis' => $monAvis,
        'nbAvis' => $nbAvis,
        'moyenne' => $moyenne,
        'idVoyageur' => $voyageur->getId(),
    ]);
}

    // ── PLANNING : Liste du planning du voyageur ─────────────────────────
    #[Route('/planning', name: 'planning')]
    public function planning(
        Request               $request,
        PlanningRepository    $planningRepo,
        UtilisateurRepository $userRepo
    ): Response {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->redirectToRoute('app_login');
        }

        $plannings = $planningRepo->findByVoyageur($voyageur->getId());

        // Grouper par date
        $planningsParDate = [];
        $total = 0;
        foreach ($plannings as $p) {
            $dateKey = $p->getDate()->format('Y-m-d');
            $planningsParDate[$dateKey][] = $p;
            $total += $p->getActivite()->getPrix() * $p->getNombrePlaces();
        }
        ksort($planningsParDate);

        return $this->render('/activite/voyageur/planning.html.twig', [
            'plannings'        => $plannings,
            'planningsParDate' => $planningsParDate,
            'total'            => $total,
            'voyageur'         => $voyageur,
        ]);
    }

    // ── AJOUTER AU PLANNING ──────────────────────────────────────────────
    // ── AJOUTER AU PLANNING ──────────────────────────────────────────────
#[Route('/planning/ajouter/{id}', name: 'planning_ajouter', methods: ['POST'])]
public function ajouterPlanning(
    int                    $id,
    Request                $request,
    ActiviteRepository     $activiteRepo,
    PlanningRepository     $planningRepo,
    EntityManagerInterface $em,
    UtilisateurRepository  $userRepo
): Response {
    $voyageur = $this->getVoyageur($request, $userRepo);
    if (!$voyageur) {
        return $this->redirectToRoute('app_login');
    }

    $activite = $activiteRepo->find($id);
    if (!$activite) {
        $this->addFlash('error', 'Activité introuvable.');
        return $this->redirectToRoute('voyageur_index');
    }

    if ($planningRepo->isAlreadyInPlanning($voyageur->getId(), $id)) {
        $this->addFlash('error', '« ' . $activite->getNom() . ' » est déjà dans votre planning.');
        return $this->redirectToRoute('voyageur_index');
    }

    $nombrePlaces = (int) $request->request->get('nombrePlaces', 1);
    $nombrePlaces = max(1, min($nombrePlaces, $activite->getNombrePlaces()));

    // ✅ Vérifier qu'il reste assez de places
    if ($activite->getNombrePlaces() < $nombrePlaces) {
        $this->addFlash('error', 'Pas assez de places disponibles.');
        return $this->redirectToRoute('voyageur_index');
    }

    $dateStr = $request->request->get('date');
    $date = $dateStr ? \DateTime::createFromFormat('Y-m-d', $dateStr) : new \DateTime();

    $heureDebutStr = $request->request->get('heureDebut');
    $heureFinStr   = $request->request->get('heureFin');
    $heureDebut = $heureDebutStr ? \DateTime::createFromFormat('H:i', $heureDebutStr) : null;
    $heureFin   = $heureFinStr   ? \DateTime::createFromFormat('H:i', $heureFinStr)   : null;

    if ($heureDebut && $heureFin) {
        if ($heureFin <= $heureDebut) {
            $this->addFlash('error', '⏰ L\'heure de fin doit être après l\'heure de début.');
            return $this->redirectToRoute('voyageur_activite_detail', ['id' => $id]);
        }
        if ($planningRepo->hasConflict($voyageur->getId(), $date, $heureDebut, $heureFin)) {
            $this->addFlash('error', '⏰ Vous avez déjà une activité prévue sur ce créneau horaire.');
            return $this->redirectToRoute('voyageur_activite_detail', ['id' => $id]);
        }
    }

    $planning = new Planning();
    $planning->setIdVoyageur($voyageur->getId());
    $planning->setActivite($activite);
    $planning->setDate($date);
    $planning->setHeureDebut($heureDebut);
    $planning->setHeureFin($heureFin);
    $planning->setNombrePlaces($nombrePlaces);

    // ✅ Décrémenter les places disponibles
    $activite->setNombrePlaces($activite->getNombrePlaces() - $nombrePlaces);

    $em->persist($planning);
    $em->persist($activite);
    $em->flush();

    $this->addFlash('success', '« ' . $activite->getNom() . ' » ajouté à votre planning !');
    return $this->redirectToRoute('voyageur_index');
}

    // ── SUPPRIMER DU PLANNING ────────────────────────────────────────────
    #[Route('/planning/supprimer/{key}', name: 'planning_supprimer', methods: ['POST'])]
public function supprimerPlanning(
    string                 $key,
    Request                $request,
    PlanningRepository     $planningRepo,
    EntityManagerInterface $em,
    UtilisateurRepository  $userRepo
): Response {
    $voyageur = $this->getVoyageur($request, $userRepo);
    if (!$voyageur) {
        return $this->redirectToRoute('app_login');
    }

    $parts = explode('_', $key);
    if (count($parts) < 3) {
        $this->addFlash('error', 'Clé invalide.');
        return $this->redirectToRoute('voyageur_planning');
    }

    [$idVoyageur, $idActivite, $date] = $parts;

    $planning = $planningRepo->createQueryBuilder('p')
        ->where('p.idVoyageur = :v')
        ->andWhere('p.activite = :a')
        ->andWhere('p.date = :d')
        ->setParameter('v', (int) $idVoyageur)
        ->setParameter('a', (int) $idActivite)
        ->setParameter('d', new \DateTime($date))
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if ($planning) {
        // ✅ Restituer les places à l'activité
        $activite = $planning->getActivite();
        $activite->setNombrePlaces(
            $activite->getNombrePlaces() + $planning->getNombrePlaces()
        );
        $em->persist($activite);

        $em->remove($planning);
        $em->flush();
        $this->addFlash('success', 'Activité retirée de votre planning.');
    } else {
        $this->addFlash('error', 'Entrée de planning introuvable.');
    }

    return $this->redirectToRoute('voyageur_planning');
}

    // ── CARTE INTERACTIVE D'UN JOUR ──────────────────────────────────────────
    #[Route('/planning/carte/{date}', name: 'planning_carte')]
    public function planningCarte(
        string                $date,
        Request               $request,
        PlanningRepository    $planningRepo,
        UtilisateurRepository $userRepo
    ): Response {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->redirectToRoute('app_login');
        }

        $dateObj   = new \DateTime($date);
        $plannings = $planningRepo->findByVoyageurAndDate($voyageur->getId(), $dateObj);

        usort($plannings, fn($a, $b) =>
            ($a->getHeureDebut()?->getTimestamp() ?? 0) <=> ($b->getHeureDebut()?->getTimestamp() ?? 0)
        );

        return $this->render('activite/voyageur/carte_journee.html.twig', [
            'plannings' => $plannings,
            'date'      => $dateObj,
            'voyageur'  => $voyageur,
        ]);
    }

    // ── RECOMMANDATION IA ────────────────────────────────────────────────────
    #[Route('/recommandation', name: 'recommandation')]
    public function recommandation(
        Request               $request,
        UtilisateurRepository $userRepo
    ): Response {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('activite/voyageur/recommandation.html.twig', [
            'voyageur' => $voyageur,
        ]);
    }

    // ── RECOMMANDATION IA — endpoint AJAX ───────────────────────────────────
    #[Route('/recommandation/analyser', name: 'recommandation_analyser', methods: ['POST'])]
    public function analyserRecommandation(
        Request               $request,
        ActiviteRepository    $activiteRepo,
        UtilisateurRepository $userRepo
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $preferences = json_decode($request->getContent(), true) ?? [];

        // Récupérer les activités disponibles
        $activites = array_filter(
            $activiteRepo->findDisponibles(),
            fn($a) => $a->getNombrePlaces() > 0
        );

        // Construire le prompt Gemini
        $lines = [];
        foreach ($activites as $a) {
            $h = $a->getHeureDebut() ? $a->getHeureDebut()->format('H:i') : 'libre';
            $lines[] = $a->getIdActivite().'|'.$a->getNom().'|'.$a->getCategorie().'|'
                      .$a->getVille().'|'.(int)$a->getPrix().'|'.$h;
        }

        $prefStr = '';
        foreach ($preferences as $key => $vals) {
            $prefStr .= '- '.$key.' : '.implode(', ', (array)$vals)."\n";
        }

        $prompt = "Tu es un expert en recommandation de voyages en Tunisie.\n"
            ."Préférences du voyageur :\n".$prefStr
            ."\nActivités disponibles (ID|NOM|CATEGORIE|VILLE|PRIX|HEURE) :\n"
            .implode("\n", $lines)."\n"
            ."\nRéponds UNIQUEMENT en JSON strict :\n"
            ."{\"ids\":[id1,id2,id3,id4,id5,id6],\"message\":\"Message motivant 2 phrases max en français\"}\n"
            ."6 activités les mieux adaptées, triées du plus au moins recommandé.";

        // Appel Gemini
        $apiKey = 'AIzaSyCf27NUT16mbYgfthqluyQJIv8bkpndfz4';
        $url    = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key='.$apiKey;

        $body = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 512],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Extraire le texte de la réponse Gemini
        $iaText = '';
        if ($code === 200 && $raw) {
            $decoded = json_decode($raw, true);
            $iaText  = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        // Parser JSON retourné par Gemini
        $ids     = [];
        $message = 'Voici vos recommandations personnalisées !';
        if ($iaText) {
            // Extraire le bloc JSON de la réponse
            if (preg_match('/\{.*\}/s', $iaText, $m)) {
                $parsed = json_decode($m[0], true);
                if ($parsed) {
                    $ids     = $parsed['ids']     ?? [];
                    $message = $parsed['message'] ?? $message;
                }
            }
        }

        // Récupérer les activités recommandées
        $activitesMap = [];
        foreach ($activites as $a) {
            $activitesMap[$a->getIdActivite()] = $a;
        }

        $recommended = [];
        foreach ($ids as $id) {
            if (isset($activitesMap[$id])) {
                $a = $activitesMap[$id];
                $recommended[] = [
                    'id'          => $a->getIdActivite(),
                    'nom'         => $a->getNom(),
                    'categorie'   => (string)$a->getCategorie(),
                    'ville'       => $a->getVille(),
                    'lieu'        => $a->getLieu(),
                    'prix'        => $a->getPrix(),
                    'image'       => $a->getImage(),
                    'heureDebut'  => $a->getHeureDebut()?->format('H:i'),
                    'heureFin'    => $a->getHeureFin()?->format('H:i'),
                    'date'        => $a->getDateActivite()?->format('Y-m-d'),
                    'nombrePlaces'=> $a->getNombrePlaces(),
                ];
            }
        }

        // Compléter avec des activités au hasard si moins de 6
        if (count($recommended) < 6) {
            foreach ($activites as $a) {
                if (count($recommended) >= 6) break;
                $alreadyIn = array_filter($recommended, fn($r) => $r['id'] === $a->getIdActivite());
                if (!$alreadyIn) {
                    $recommended[] = [
                        'id'          => $a->getIdActivite(),
                        'nom'         => $a->getNom(),
                        'categorie'   => (string)$a->getCategorie(),
                        'ville'       => $a->getVille(),
                        'lieu'        => $a->getLieu(),
                        'prix'        => $a->getPrix(),
                        'image'       => $a->getImage(),
                        'heureDebut'  => $a->getHeureDebut()?->format('H:i'),
                        'heureFin'    => $a->getHeureFin()?->format('H:i'),
                        'date'        => $a->getDateActivite()?->format('Y-m-d'),
                        'nombrePlaces'=> $a->getNombrePlaces(),
                    ];
                }
            }
        }

        return $this->json([
            'activites' => array_values($recommended),
            'message'   => $message,
        ]);
    }
}
