<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        // View part is intentionally omitted per your request.
        // Returning JSON data instead.
        return $this->json([
            'message' => 'UtilisateurController index',
            // 'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }
}
