<?php
namespace App\Controller;

use App\Entity\AvisActivite;
use App\Entity\Activite;
use App\Repository\AvisActiviteRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/avis')]
class AvisActiviteController extends AbstractController
{
    // ─── HELPER : récupérer le voyageur connecté depuis la session ────────
    private function getVoyageur(Request $request, UtilisateurRepository $userRepo)
    {
        $id = $request->getSession()->get('user_id');
        if (!$id) return null;
        return $userRepo->find($id);
    }

    // ─── PUBLIER un avis ──────────────────────────────────────────────────
    #[Route('/publier/{idActivite}', name: 'avis_publier', methods: ['POST'])]
    #[Route('/publier/{idActivite}', name: 'avis_publier', methods: ['POST'])]
public function publier(
    int                    $idActivite,
    Request                $request,
    EntityManagerInterface $em,
    AvisActiviteRepository $avisRepo,
    UtilisateurRepository  $userRepo
): Response {
    $voyageur = $this->getVoyageur($request, $userRepo);
    if (!$voyageur) {
        return $this->redirectToRoute('app_login');
    }

    $activite = $em->getRepository(Activite::class)->find($idActivite);
    if (!$activite) {
        throw $this->createNotFoundException('Activité non trouvée');
    }

    $commentaire = trim($request->request->get('commentaire', ''));
    $note        = (int) $request->request->get('note', 5);

    if (empty($commentaire)) {
        $this->addFlash('error_avis', 'Le commentaire ne peut pas être vide.');
        return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
    }
    if ($note < 1 || $note > 5) {
        $this->addFlash('error_avis', 'La note doit être entre 1 et 5.');
        return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
    }

    $existant = $avisRepo->findMonAvis($voyageur->getId(), $idActivite);
    if ($existant) {
        $this->addFlash('error_avis', 'Vous avez déjà publié un avis pour cette activité.');
        return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
    }

    // ✅ Correct entity class
    $avis = new AvisActivite();
    $avis->setActivite($activite);
    $avis->setIdVoyageur($voyageur->getId());
    $avis->setNomVoyageur($voyageur->getPrenom() . ' ' . $voyageur->getNom());
    $avis->setNote($note);
    $avis->setCommentaire($commentaire);

    $em->persist($avis);
    $em->flush();

    $this->addFlash('success_avis', '✅ Votre avis a été publié !');
    return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
}

    // ─── MODIFIER un avis ─────────────────────────────────────────────────
    #[Route('/modifier/{id}', name: 'avis_modifier', methods: ['POST'])]
    public function modifier(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        AvisActiviteRepository         $avisRepo,
        UtilisateurRepository  $userRepo
    ): Response {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->redirectToRoute('app_login');
        }

        $avis = $avisRepo->find($id);
        if (!$avis) {
            throw $this->createNotFoundException('Avis non trouvé');
        }

        // Sécurité : seul le propriétaire peut modifier
        if ($avis->getIdVoyageur() !== $voyageur->getId()) {  // ✅
            $this->addFlash('error_avis', 'Action non autorisée.');
            return $this->redirectToRoute('voyageur_activite_detail',
                ['id' => $avis->getActivite()->getIdActivite()]);
        }

        $commentaire = trim($request->request->get('commentaire', ''));
        $note        = (int) $request->request->get('note', 5);

        if (empty($commentaire)) {
            $this->addFlash('error_avis', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('voyageur_activite_detail',
                ['id' => $avis->getActivite()->getIdActivite()]);
        }

        $avis->setNote($note);
        $avis->setCommentaire($commentaire);
        $avis->setDateAvis(new \DateTime());
        $em->flush();

        $this->addFlash('success_avis', '✅ Avis modifié avec succès !');
        return $this->redirectToRoute('voyageur_activite_detail',
            ['id' => $avis->getActivite()->getIdActivite()]);
    }

    // ─── SUPPRIMER un avis (voyageur) ─────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'avis_supprimer', methods: ['POST'])]
    public function supprimer(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        AvisActiviteRepository         $avisRepo,
        UtilisateurRepository  $userRepo
    ): Response {
        $voyageur = $this->getVoyageur($request, $userRepo);
        if (!$voyageur) {
            return $this->redirectToRoute('app_login');
        }

        $avis = $avisRepo->find($id);
        if (!$avis) {
            throw $this->createNotFoundException('Avis non trouvé');
        }

        $idActivite = $avis->getActivite()->getIdActivite();

        if ($avis->getIdVoyageur() !== $voyageur->getId()) {  // ✅
            $this->addFlash('error_avis', 'Action non autorisée.');
            return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
        }

        $em->remove($avis);
        $em->flush();

        $this->addFlash('success_avis', '✅ Avis supprimé.');
        return $this->redirectToRoute('voyageur_activite_detail', ['id' => $idActivite]);
    }

    // ─── SUPPRIMER un avis (admin) ────────────────────────────────────────
    #[Route('/admin/supprimer/{id}', name: 'admin_avis_supprimer', methods: ['POST'])]
public function supprimerAdmin(
    int                    $id,
    Request                $request,
    EntityManagerInterface $em,
    AvisActiviteRepository         $avisRepo
): Response {
    $avis = $avisRepo->find($id);
    $referer = $request->headers->get('referer');

    if ($avis) {
        $em->remove($avis);
        $em->flush();
        $this->addFlash('success', '🗑️ Avis supprimé avec succès.');
    }

    // Retourne à la page précédente (détail activité ou liste avis)
    return $referer
        ? $this->redirect($referer)
        : $this->redirectToRoute('admin_avis_index');
}

}