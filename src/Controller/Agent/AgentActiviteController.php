<?php

namespace App\Controller\Agent;

use App\Repository\UtilisateurRepository;
use App\Entity\Activite;
use App\Entity\Utilisateur;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GeminiService;
use App\Service\UnsplashService;  
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/agent/activites', name: 'agent_activite_')]
class AgentActiviteController extends AbstractController
{
    /**
     * Récupère l'agent connecté depuis la session UNIQUEMENT.
     * getFakeAgent() est supprimée — elle était la source du bug.
     */
    private function getAgentConnecte(
        Request $request,
        UtilisateurRepository $userRepo
    ): ?Utilisateur {
        $id   = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');

        if (!$id || $role !== 'AGENT') {
            return null;
        }

        return $userRepo->find($id);
    }

    // ── INDEX ──────────────────────────────────────────────────────────────
    #[Route('', name: 'index')]
    public function index(
        Request               $request,
        ActiviteRepository    $activiteRepo,
        UtilisateurRepository $userRepo
    ): Response {
        $agent = $this->getAgentConnecte($request, $userRepo);
        if (!$agent) {
            return $this->redirectToRoute('app_login');
        }

        $search        = $request->query->get('q', '');
        $categorie     = $request->query->get('categorie', '');
        $disponibilite = $request->query->get('disponibilite', 'toutes');

        $activites  = $activiteRepo->findByAgent(
            $agent->getId(), $search, $categorie, $disponibilite
        );
        $categories = $activiteRepo->findCategoriesByAgent($agent->getId());

        $stats = [
            'total'      => count($activites),
            'disponible' => count(array_filter($activites, fn(Activite $a) => $a->isDisponible())),
            'completes'  => count(array_filter($activites, fn(Activite $a) => !$a->isDisponible())),
        ];

        return $this->render('/activite/agent/index.html.twig', [
            'agent'         => $agent,
            'activites'     => $activites,
            'categories'    => $categories,
            'stats'         => $stats,
            'search'        => $search,
            'categorie'     => $categorie,
            'disponibilite' => $disponibilite,
        ]);
    }

    // ── NEW ────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'new')]
    public function new(
        Request                $request,
        EntityManagerInterface $em,
        ActiviteRepository     $activiteRepo,
        UtilisateurRepository  $userRepo
    ): Response {
        // ✅ Utilise getAgentConnecte — plus getFakeAgent !
        $agent = $this->getAgentConnecte($request, $userRepo);
        if (!$agent) {
            return $this->redirectToRoute('app_login');
        }

        $activite = new Activite();
        // ✅ L'activité est rattachée à l'agent réellement connecté
        $activite->setIdAgent($agent->getId());

        $form = $this->createForm(ActiviteType::class, $activite, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$activiteRepo->isNomUnique($activite->getNom())) {
                $this->addFlash('error', 'Une activité avec ce nom existe déjà.');
                return $this->render('/activite/agent/new.html.twig', [
                    'form' => $form, 'agent' => $agent,
                ]);
            }

            if ($activite->getHeureFin() !== null
                && $activite->getHeureDebut() !== null
                && $activite->getHeureFin() <= $activite->getHeureDebut()
            ) {
                $this->addFlash('error', "L'heure de fin doit être après l'heure de début.");
                return $this->render('/activite/agent/new.html.twig', [
                    'form' => $form, 'agent' => $agent,
                ]);
            }

            $imageFile = $form->get('imageFile')->getData();

if ($imageFile) {
    // Upload manuel → prioritaire
    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
    $imageFile->move($this->getParameter('images_directory'), $newFilename);
    $activite->setImage($newFilename);
} else {
    // ✅ Photo Unsplash déjà téléchargée → on récupère le chemin depuis le champ caché
    $autoImagePath = $request->request->get('autoImagePath', '');
    if (!empty($autoImagePath)) {
        $activite->setImage($autoImagePath);
    }
}

            foreach ($form->get('voyages')->getData() as $voyage) {
                $activite->addVoyage($voyage);
            }

            $em->persist($activite);
            $em->flush();

            $this->addFlash('success', 'Activité créée avec succès !');
            return $this->redirectToRoute('agent_activite_index');
        }

        return $this->render('/activite/agent/new.html.twig', [
            'form' => $form, 'agent' => $agent,
        ]);
    }

    // ── EDIT ───────────────────────────────────────────────────────────────
    #[Route('/edit/{id}', name: 'edit')]
    public function edit(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        ActiviteRepository     $activiteRepo,
        UtilisateurRepository  $userRepo
    ): Response {
        // ✅ Utilise getAgentConnecte — plus getFakeAgent !
        $agent = $this->getAgentConnecte($request, $userRepo);
        if (!$agent) {
            return $this->redirectToRoute('app_login');
        }

        $activite = $em->getRepository(Activite::class)->find($id);

        if (!$activite) {
            $this->addFlash('error', 'Activité introuvable.');
            return $this->redirectToRoute('agent_activite_index');
        }

        // ✅ Sécurité : un agent ne peut modifier que SES activités
        if ($activite->getIdAgent() !== $agent->getId()) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('agent_activite_index');
        }

        $form = $this->createForm(ActiviteType::class, $activite, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$activiteRepo->isNomUnique($activite->getNom(), $activite->getIdActivite())) {
                $this->addFlash('error', 'Une activité avec ce nom existe déjà.');
                return $this->render('/activite/agent/edit.html.twig', [
                    'form' => $form, 'activite' => $activite, 'agent' => $agent,
                ]);
            }

            if ($activite->getHeureFin() !== null
                && $activite->getHeureDebut() !== null
                && $activite->getHeureFin() <= $activite->getHeureDebut()
            ) {
                $this->addFlash('error', "L'heure de fin doit être après l'heure de début.");
                return $this->render('/activite/agent/edit.html.twig', [
                    'form' => $form, 'activite' => $activite, 'agent' => $agent,
                ]);
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $activite->setImage($newFilename);
            }

            foreach ($activite->getVoyages() as $v) {
                $activite->removeVoyage($v);
            }
            foreach ($form->get('voyages')->getData() as $voyage) {
                $activite->addVoyage($voyage);
            }

            $em->flush();
            $this->addFlash('success', 'Activité modifiée avec succès !');
            return $this->redirectToRoute('agent_activite_index');
        }

        return $this->render('/activite/agent/edit.html.twig', [
            'form' => $form, 'activite' => $activite, 'agent' => $agent,
        ]);
    }

    // ── DELETE ─────────────────────────────────────────────────────────────
    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UtilisateurRepository  $userRepo
    ): Response {
        $agent = $this->getAgentConnecte($request, $userRepo);
        if (!$agent) {
            return $this->redirectToRoute('app_login');
        }

        $activite = $em->getRepository(Activite::class)->find($id);

        if (!$activite) {
            $this->addFlash('error', 'Activité introuvable.');
        // ✅ Sécurité : un agent ne peut supprimer que SES activités
        } elseif ($activite->getIdAgent() !== $agent->getId()) {
            $this->addFlash('error', 'Accès refusé.');
        } else {
            $em->remove($activite);
            $em->flush();
            $this->addFlash('success', 'Activité supprimée.');
        }

        return $this->redirectToRoute('agent_activite_index');
    }

    // ── SHOW ───────────────────────────────────────────────────────────────
    #[Route('/show/{id}', name: 'show')]
    public function show(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UtilisateurRepository  $userRepo
    ): Response {
        // ✅ Utilise getAgentConnecte — plus getFakeAgent !
        $agent = $this->getAgentConnecte($request, $userRepo);
        if (!$agent) {
            return $this->redirectToRoute('app_login');
        }

        $activite = $em->getRepository(Activite::class)->find($id);

        if (!$activite) {
            $this->addFlash('error', 'Activité introuvable.');
            return $this->redirectToRoute('agent_activite_index');
        }

        // ✅ Sécurité : un agent ne voit que SES activités
        if ($activite->getIdAgent() !== $agent->getId()) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('agent_activite_index');
        }

        return $this->render('/activite/agent/show.html.twig', [
            'activite' => $activite,
            'agent'    => $agent,
        ]);
    }

#[Route('/generate-description', name: 'generate_description', methods: ['POST'])]
public function generateDescription(Request $request, GeminiService $gemini): JsonResponse
{
    try {
        $data = json_decode($request->getContent(), true);

        $nom       = $data['nom']       ?? '';
        $ville     = $data['ville']     ?? '';
        $categorie = $data['categorie'] ?? 'Tourisme';

        $description = $gemini->genererDescription($nom, $ville, $categorie);

        if (!$description) {
            return $this->json(['error' => 'Gemini n\'a rien retourné'], 500);
        }

        return $this->json(['description' => $description]);

    } catch (\Throwable $e) {
        // ✅ Retourne le VRAI message d'erreur dans la réponse JSON
        return $this->json([
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'class'   => get_class($e),
        ], 500);
    }
}

#[Route('/auto-photo', name: 'auto_photo', methods: ['POST'])]
public function autoPhoto(
    Request $request,
    UnsplashService $unsplash,
    UtilisateurRepository $userRepo
): JsonResponse {
    $agent = $this->getAgentConnecte($request, $userRepo);
    if (!$agent) {
        return $this->json(['error' => 'Non connecté'], 401);
    }

    $data      = json_decode($request->getContent(), true);
    $nom       = $data['nom']       ?? '';
    $ville     = $data['ville']     ?? '';
    $categorie = $data['categorie'] ?? '';

    $query    = UnsplashService::buildSearchQuery($nom, $ville, $categorie);
    $filename = $unsplash->fetchAndSavePhoto($query); // doit retourner juste "nom_fichier.jpg"

    if (!$filename) {
        return $this->json(['error' => 'Aucune photo trouvée'], 404);
    }

    return $this->json([
        'filename' => $filename,
        'path'     => 'uploads/images/' . $filename,
        'url'      => '/uploads/images/' . $filename,
    ]);
}
}