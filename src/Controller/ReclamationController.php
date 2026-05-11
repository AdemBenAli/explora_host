<?php

namespace App\Controller;

use App\Repository\ReclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): JsonResponse
    {
        // View part is intentionally omitted per your request.
        return $this->json([
            'message' => 'ReclamationController index',
            // 'reclamations' => $reclamationRepository->findAll(),
        ]);
    }
}
