<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\HebergementRepository;
use App\Service\AvisSentimentService;
use App\Service\GeminiReviewSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/avis')]
final class AvisController extends AbstractController
{
    #[Route('/predict', name: 'app_avis_predict', methods: ['GET'])]
    public function predict(Request $request, AvisSentimentService $sentimentService): JsonResponse
    {
        $comment = trim((string) $request->query->get('comment', ''));
        $stars = $sentimentService->predictStars($comment);

        return $this->json([
            'success' => true,
            'stars' => $stars,
        ]);
    }

    #[Route('/create', name: 'app_avis_create', methods: ['POST'])]
    public function create(
        Request $request,
        HebergementRepository $hebergementRepository,
        AvisRepository $avisRepository,
        AvisSentimentService $sentimentService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('create_avis', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $hebergementId = (int) $request->request->get('hebergementId');
        $commentaire = trim((string) $request->request->get('commentaire', ''));
        $nomAuteur = trim((string) $request->request->get('nomAuteur', 'Guest'));

        $hebergement = $hebergementRepository->find($hebergementId);

        if (!$hebergement) {
            return $this->json([
                'success' => false,
                'message' => 'Hôtel introuvable.',
            ], 404);
        }

        if ($commentaire === '') {
            return $this->json([
                'success' => false,
                'message' => 'Merci d’écrire un commentaire.',
            ], 400);
        }

        $stars = $sentimentService->predictStars($commentaire);
        if ($stars < 1 || $stars > 5) {
            $stars = 3;
        }

        $avis = new Avis();
        $avis
            ->setHebergement($hebergement)
            ->setNomAuteur($nomAuteur !== '' ? $nomAuteur : 'Guest')
            ->setNote($stars)
            ->setCommentaire($commentaire)
            ->setDateAvis(new \DateTime());

        $entityManager->persist($avis);
        $entityManager->flush();

        $hebergement->setNoteMoyenne($avisRepository->getAverageForHebergement($hebergement));
        $entityManager->flush();

        $summary = $avisRepository->getSummariesForHebergements([$hebergement->getId()]);
        $hotelSummary = $summary[$hebergement->getId()] ?? [
            'avg5' => 0.0,
            'count' => 0,
            'score10' => 0.0,
            'starsRounded' => 0,
        ];

        return $this->json([
            'success' => true,
            'message' => sprintf('Merci ! Votre avis a été ajouté. (%d/5 auto)', $stars),
            'review' => [
                'author' => $avis->getNomAuteur(),
                'note' => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
                'dateAvis' => $avis->getDateAvis()?->format('Y-m-d H:i:s'),
            ],
            'summary' => $hotelSummary,
        ]);
    }

    #[Route('/hebergement/{id}', name: 'app_avis_list_for_hebergement', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function listForHebergement(
        int $id,
        HebergementRepository $hebergementRepository,
        AvisRepository $avisRepository
    ): JsonResponse {
        $hebergement = $hebergementRepository->find($id);

        if (!$hebergement) {
            return $this->json([
                'success' => false,
                'message' => 'Hôtel introuvable.',
            ], 404);
        }

        $avisList = $avisRepository->findByHebergementOrdered($hebergement);
        $summary = $avisRepository->getSummariesForHebergements([$hebergement->getId()]);
        $hotelSummary = $summary[$hebergement->getId()] ?? [
            'avg5' => 0.0,
            'count' => 0,
            'score10' => 0.0,
            'starsRounded' => 0,
        ];

        $reviews = array_map(static function (Avis $avis): array {
            return [
                'id' => $avis->getId(),
                'author' => $avis->getNomAuteur() ?: 'Guest',
                'note' => (int) ($avis->getNote() ?? 0),
                'commentaire' => $avis->getCommentaire() ?? '',
                'dateAvis' => $avis->getDateAvis()?->format('Y-m-d H:i:s'),
            ];
        }, $avisList);

        return $this->json([
            'success' => true,
            'hotel' => [
                'id' => $hebergement->getId(),
                'nom' => $hebergement->getNom(),
            ],
            'summary' => $hotelSummary,
            'reviews' => $reviews,
        ]);
    }

    #[Route('/hebergement/{id}/summary', name: 'app_avis_generate_summary', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generateSummary(
        int $id,
        Request $request,
        HebergementRepository $hebergementRepository,
        AvisRepository $avisRepository,
        GeminiReviewSummaryService $geminiReviewSummaryService
    ): JsonResponse {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('generate_ai_summary', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $hebergement = $hebergementRepository->find($id);

        if (!$hebergement) {
            return $this->json([
                'success' => false,
                'message' => 'Hôtel introuvable.',
            ], 404);
        }

        $avisList = $avisRepository->findByHebergementOrdered($hebergement);
        $comments = [];

        foreach ($avisList as $avis) {
            $comment = trim((string) $avis->getCommentaire());
            if ($comment !== '') {
                $comments[] = $comment;
            }
        }

        try {
            $summaryText = $geminiReviewSummaryService->generateSummaryText($comments);
        } catch (\Throwable $e) {
            $summaryText = '❌ Erreur IA: ' . $e->getMessage();
        }

        return $this->json([
            'success' => true,
            'summary' => $summaryText,
        ]);
    }
}