<?php
// src/Controller/ActiviteController.php

namespace App\Controller;

use App\Entity\Activite;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GeminiService;
use App\Service\UnsplashService;

#[Route('/activite')]
class ActiviteController extends AbstractController
{
    // ─── INDEX ────────────────────────────────────────────────────────────────
    #[Route('/', name: 'activite_index')]
    public function index(ActiviteRepository $repo): Response
    {
        return $this->render('activite/index.html.twig', [
            'activites' => $repo->findAll(),
        ]);
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────
    #[Route('/new', name: 'activite_new')]
    public function new(Request $request, EntityManagerInterface $em, ActiviteRepository $repo): Response
    {
        $activite = new Activite();

        // is_edit = false → nombrePlaces doit être > 0
        $form = $this->createForm(ActiviteType::class, $activite, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Unicité du nom ────────────────────────────────────────────────
            if (!$repo->isNomUnique($activite->getNom())) {
                $this->addFlash('error', 'Une activité avec le nom "' . $activite->getNom() . '" existe déjà. Veuillez choisir un nom unique.');
                return $this->render('activite/new.html.twig', ['form' => $form]);
            }

            // ── Heure fin > Heure début ───────────────────────────────────────
            if ($activite->getHeureFin() !== null
                && $activite->getHeureDebut() !== null
                && $activite->getHeureFin() <= $activite->getHeureDebut()
            ) {
                $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début.');
                return $this->render('activite/new.html.twig', ['form' => $form]);
            }

            // ── Image ─────────────────────────────────────────────────────────
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $activite->setImage($newFilename);
            } else {
                $autoImagePath = $request->request->get('autoImagePath', '');
                if (!empty($autoImagePath)) {
                    $activite->setImage($autoImagePath);
                }
            }

            // ── Voyages associés ──────────────────────────────────────────────
            foreach ($form->get('voyages')->getData() as $voyage) {
                $activite->addVoyage($voyage);
            }

            $em->persist($activite);
            $em->flush();

            $this->addFlash('success', 'Activité créée avec succès !');
            return $this->redirectToRoute('admin_activite_index');
        }

        return $this->render('activite/new.html.twig', ['form' => $form]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────
    #[Route('/edit/{id}', name: 'activite_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, ActiviteRepository $repo): Response
    {
        $activite = $em->getRepository(Activite::class)->find($id);
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        // is_edit = true → nombrePlaces peut être 0
        $form = $this->createForm(ActiviteType::class, $activite, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Unicité du nom (on exclut l'activité en cours) ────────────────
            if (!$repo->isNomUnique($activite->getNom(), $activite->getIdActivite())) {
                $this->addFlash('error', 'Une activité avec le nom "' . $activite->getNom() . '" existe déjà. Veuillez choisir un nom unique.');
                return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
            }

            // ── Heure fin > Heure début ───────────────────────────────────────
            if ($activite->getHeureFin() !== null
                && $activite->getHeureDebut() !== null
                && $activite->getHeureFin() <= $activite->getHeureDebut()
            ) {
                $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début.');
                return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
            }

            // ── Image ─────────────────────────────────────────────────────────
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $activite->setImage($newFilename);
            } else {
                $autoImagePath = $request->request->get('autoImagePath', '');
                if (!empty($autoImagePath)) {
                    $activite->setImage($autoImagePath);
                }
            }

            // ── Voyages associés ──────────────────────────────────────────────
            foreach ($activite->getVoyages() as $v) {
                $activite->removeVoyage($v);
            }
            foreach ($form->get('voyages')->getData() as $voyage) {
                $activite->addVoyage($voyage);
            }

            $em->flush();
            $this->addFlash('success', 'Activité modifiée avec succès !');
            return $this->redirectToRoute('admin_activite_index');
        }

        return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────
    #[Route('/delete/{id}', name: 'activite_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $activite = $em->getRepository(Activite::class)->find($id);
        if ($activite) {
            $em->remove($activite);
            $em->flush();
            $this->addFlash('success', 'Activité supprimée.');
        }
        return $this->redirectToRoute('admin_activite_index');
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    #[Route('/show/{id}', name: 'activite_show')]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $activite = $em->getRepository(Activite::class)->find($id);
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    // ─── EDIT MODAL (même logique qu'edit, même template) ─────────────────────
#[Route('/edit-modal/{id}', name: 'activite_edit_modal')]
public function editModal(int $id, Request $request, EntityManagerInterface $em, ActiviteRepository $repo): Response
{
    // Exactement la même logique que edit()
    $activite = $em->getRepository(Activite::class)->find($id);
    if (!$activite) {
        throw $this->createNotFoundException('Activité non trouvée');
    }

    $form = $this->createForm(ActiviteType::class, $activite, ['is_edit' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        if (!$repo->isNomUnique($activite->getNom(), $activite->getIdActivite())) {
            $this->addFlash('error', 'Une activité avec ce nom existe déjà.');
            return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
        }

        if ($activite->getHeureFin() !== null
            && $activite->getHeureDebut() !== null
            && $activite->getHeureFin() <= $activite->getHeureDebut()
        ) {
            $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début.');
            return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
        }

        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('images_directory'), $newFilename);
            $activite->setImage($newFilename);
        } else {
            $autoImagePath = $request->request->get('autoImagePath', '');
            if (!empty($autoImagePath)) {
                $activite->setImage($autoImagePath);
            }
        }

        foreach ($activite->getVoyages() as $v) {
            $activite->removeVoyage($v);
        }
        foreach ($form->get('voyages')->getData() as $voyage) {
            $activite->addVoyage($voyage);
        }

        $em->flush();
        $this->addFlash('success', 'Activité modifiée avec succès !');
        return $this->redirectToRoute('admin_activite_index');
    }

    return $this->render('activite/edit.html.twig', ['form' => $form, 'activite' => $activite]);
}

    // ─── API IA ───────────────────────────────────────────────────────────────
    #[Route('/generate-description', name: 'activite_generate_description', methods: ['POST'])]
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
            return $this->json([
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'class'   => get_class($e),
            ], 500);
        }
    }

    #[Route('/auto-photo', name: 'activite_auto_photo', methods: ['POST'])]
    public function autoPhoto(Request $request, UnsplashService $unsplash): JsonResponse
    {
        // Administrateur autorisé
        $data      = json_decode($request->getContent(), true);
        $nom       = $data['nom']       ?? '';
        $ville     = $data['ville']     ?? '';
        $categorie = $data['categorie'] ?? '';

        $query    = UnsplashService::buildSearchQuery($nom, $ville, $categorie);
        $filename = $unsplash->fetchAndSavePhoto($query);

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