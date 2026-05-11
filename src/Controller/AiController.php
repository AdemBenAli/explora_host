<?php

namespace App\Controller;

use App\Repository\VoyageRepository;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ai')]
class AiController extends AbstractController
{
    #[Route('/description', name: 'api_ai_description', methods: ['POST'])]
    public function generateDescription(Request $request, GeminiService $geminiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $titre = $data['titre'] ?? '';
        $destination = $data['destination'] ?? '';

        if (empty($titre) || empty($destination)) {
            return new JsonResponse(['error' => 'Le titre et la destination sont requis.'], 400);
        }

        $description = $geminiService->generateDescription($titre, $destination);
        
        return new JsonResponse(['description' => $description]);
    }

    #[Route('/chat', name: 'api_ai_chat', methods: ['POST'])]
    public function chat(Request $request, GeminiService $geminiService, VoyageRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse(['error' => 'Le message est requis.'], 400);
        }

        $voyages = $repository->findAll();
        $reply = $geminiService->chat($message, $voyages);

        return new JsonResponse(['reply' => $reply]);
    }
}
