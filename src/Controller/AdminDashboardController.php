<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Panier;
use App\Entity\ProduitPanier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin', name: 'admin_')]
class AdminDashboardController extends AbstractController
{
    #[Route('/carts/export', name: 'carts_export', methods: ['GET'])]
    public function exportCarts(EntityManagerInterface $entityManager): Response
    {
        $paniers = $entityManager->getRepository(Panier::class)->findBy([], ['id' => 'DESC']);

        $userIds = array_values(array_unique(array_filter(array_map(
            static fn(Panier $panier): ?int => $panier->getUserId(),
            $paniers
        ))));

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userIds as $id) { $u = new class { public int $id; public function getId() { return $this->id; } public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $u->id = $id; $usersById[$id] = $u; }
        }

        $itemCounts = [];
        if ($paniers !== []) {
            $panierIds = array_values(array_map(static fn(Panier $panier): ?int => $panier->getId(), $paniers));
            $qb = $entityManager->createQueryBuilder();
            $rows = $qb
                ->select('pp.panierId AS panierId, SUM(pp.quantite) AS itemsCount')
                ->from(ProduitPanier::class, 'pp')
                ->where($qb->expr()->in('pp.panierId', ':panierIds'))
                ->setParameter('panierIds', $panierIds)
                ->groupBy('pp.panierId')
                ->getQuery()
                ->getArrayResult();

            foreach ($rows as $row) {
                $itemCounts[(int) $row['panierId']] = (int) $row['itemsCount'];
            }
        }

        $exportRows = [];
        foreach ($paniers as $panier) {
            $user = $usersById[(int) $panier->getUserId()] ?? null;
            $exportRows[] = [
                (int) $panier->getId(),
                trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? ''))) ?: 'Unknown User',
                $user?->getEmail() ?? 'N/A',
                $itemCounts[(int) $panier->getId()] ?? 0,
                (float) ($panier->getMontantTtc() ?? 0),
                strtoupper((string) $panier->getStatut()),
                $panier->getDateCreation()?->format('Y-m-d H:i:s') ?? '',
                $panier->getDateModification()?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        return $this->buildCsvDownloadResponse(
            'carts_export_' . (new \DateTime())->format('Ymd_His') . '.csv',
            ['Cart ID', 'Customer', 'Email', 'Items', 'Total', 'Status', 'Created At', 'Updated At'],
            $exportRows
        );
    }

    #[Route('/payments/export', name: 'payments_export', methods: ['GET'])]
    public function exportPayments(EntityManagerInterface $entityManager): Response
    {
        $paiements = $entityManager->getRepository(Paiement::class)->findBy([], ['id' => 'DESC']);

        $panierIds = array_values(array_unique(array_filter(array_map(
            static fn(Paiement $paiement): ?int => $paiement->getPanierId(),
            $paiements
        ))));

        $paniersById = [];
        if ($panierIds !== []) {
            $paniers = $entityManager->getRepository(Panier::class)->findBy(['id' => $panierIds]);
            foreach ($paniers as $panier) {
                $paniersById[(int) $panier->getId()] = $panier;
            }
        }

        $userIds = [];
        foreach ($paniersById as $panier) {
            if ($panier->getUserId() !== null) {
                $userIds[] = (int) $panier->getUserId();
            }
        }
        $userIds = array_values(array_unique($userIds));

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userIds as $id) { $u = new class { public int $id; public function getId() { return $this->id; } public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $u->id = $id; $usersById[$id] = $u; }
        }

        $exportRows = [];
        foreach ($paiements as $paiement) {
            $panier = $paniersById[(int) $paiement->getPanierId()] ?? null;
            $user = $panier ? ($usersById[(int) $panier->getUserId()] ?? null) : null;

            $exportRows[] = [
                (int) $paiement->getId(),
                (int) ($paiement->getPanierId() ?? 0),
                trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? ''))) ?: 'Unknown User',
                (float) ($paiement->getMontantPaye() ?? 0),
                strtoupper((string) $paiement->getMethodePaiement()),
                strtoupper((string) $paiement->getStatut()),
                $paiement->getDatePaiement()?->format('Y-m-d H:i:s') ?? '',
                $paiement->getReferenceTransaction() ?: ('PAY-' . $paiement->getId()),
            ];
        }

        return $this->buildCsvDownloadResponse(
            'payments_export_' . (new \DateTime())->format('Ymd_His') . '.csv',
            ['Payment ID', 'Order ID', 'Customer', 'Amount', 'Method', 'Status', 'Date', 'Transaction ID'],
            $exportRows
        );
    }

    #[Route('/carts/{id}/update', name: 'carts_update', methods: ['POST'])]
    public function updateCart(Panier $panier, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $status = strtoupper(trim((string) $request->request->get('status', '')));
        $allowedStatuses = ['ACTIF', 'VALIDE', 'ABANDONNE'];

        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('error', 'Invalid cart status.');
            return $this->redirectToRoute('admin_carts_dashboard');
        }

        $panier->setStatut($status);
        $panier->setDateModification(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', sprintf('Cart %d updated.', (int) $panier->getId()));

        return $this->redirectToRoute('admin_carts_dashboard');
    }

    #[Route('/carts', name: 'carts_dashboard', methods: ['GET'])]
    public function carts(EntityManagerInterface $entityManager, ChartBuilderInterface $chartBuilder): Response
    {
        $paniers = $entityManager->getRepository(Panier::class)->findBy([], ['id' => 'DESC']);

        $userIds = array_values(array_unique(array_filter(array_map(
            static fn(Panier $panier): ?int => $panier->getUserId(),
            $paniers
        ))));

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userIds as $id) { $u = new class { public int $id; public function getId() { return $this->id; } public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $u->id = $id; $usersById[$id] = $u; }
        }

        $itemCounts = [];
        if ($paniers !== []) {
            $panierIds = array_values(array_map(static fn(Panier $panier): ?int => $panier->getId(), $paniers));
            $qb = $entityManager->createQueryBuilder();
            $rows = $qb
                ->select('pp.panierId AS panierId, SUM(pp.quantite) AS itemsCount')
                ->from(ProduitPanier::class, 'pp')
                ->where($qb->expr()->in('pp.panierId', ':panierIds'))
                ->setParameter('panierIds', $panierIds)
                ->groupBy('pp.panierId')
                ->getQuery()
                ->getArrayResult();

            foreach ($rows as $row) {
                $itemCounts[(int) $row['panierId']] = (int) $row['itemsCount'];
            }
        }

        $carts = [];
        $active = 0;
        $abandoned = 0;
        $today = 0;
        $totalValue = 0.0;

        $todayDate = (new \DateTime())->format('Y-m-d');

        foreach ($paniers as $panier) {
            $status = strtoupper((string) $panier->getStatut());
            $user = $usersById[(int) $panier->getUserId()] ?? null;
            $amount = (float) ($panier->getMontantTtc() ?? 0);

            if ($status === 'ACTIF') {
                $active++;
            }
            if ($status === 'ABANDONNE') {
                $abandoned++;
            }

            $createdAt = $panier->getDateCreation();
            if ($createdAt && $createdAt->format('Y-m-d') === $todayDate) {
                $today++;
            }

            $totalValue += $amount;

            $carts[] = [
                'id' => (int) $panier->getId(),
                'userName' => trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? ''))) ?: 'Unknown User',
                'email' => $user?->getEmail() ?? 'N/A',
                'itemsCount' => $itemCounts[(int) $panier->getId()] ?? 0,
                'total' => $amount,
                'status' => $status,
                'createdAt' => $createdAt?->format('Y-m-d H:i:s'),
                'updatedAt' => $panier->getDateModification()?->format('Y-m-d H:i:s'),
            ];
        }

        $total = count($carts);
        $validated = max(0, $total - $active - $abandoned);

        $cartsStatsChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $cartsStatsChart->setData([
            'labels' => ['Active', 'Abandoned', 'Validated'],
            'datasets' => [[
                'data' => [$active, $abandoned, $validated],
                'backgroundColor' => ['#2ecc71', '#e74c3c', '#4f81d9'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
        ]);
        $cartsStatsChart->setOptions([
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'title' => ['display' => true, 'text' => 'Cart Status Distribution'],
            ],
        ]);

        return $this->render('admin/carts_dashboard.html.twig', [
            'carts' => $carts,
            'cartsStatsChart' => $cartsStatsChart,
            'stats' => [
                'total' => $total,
                'active' => $active,
                'abandoned' => $abandoned,
                'value' => $totalValue,
                'today' => $today,
                'activePercent' => $total > 0 ? round(($active * 100) / $total, 1) : 0,
                'abandonedPercent' => $total > 0 ? round(($abandoned * 100) / $total, 1) : 0,
                'avg' => $total > 0 ? round($totalValue / $total, 2) : 0,
            ],
        ]);
    }

    #[Route('/payments', name: 'payments_dashboard', methods: ['GET'])]
    public function payments(EntityManagerInterface $entityManager, ChartBuilderInterface $chartBuilder): Response
    {
        $paiements = $entityManager->getRepository(Paiement::class)->findBy([], ['id' => 'DESC']);

        $panierIds = array_values(array_unique(array_filter(array_map(
            static fn(Paiement $paiement): ?int => $paiement->getPanierId(),
            $paiements
        ))));

        $paniersById = [];
        if ($panierIds !== []) {
            $paniers = $entityManager->getRepository(Panier::class)->findBy(['id' => $panierIds]);
            foreach ($paniers as $panier) {
                $paniersById[(int) $panier->getId()] = $panier;
            }
        }

        $userIds = [];
        foreach ($paniersById as $panier) {
            if ($panier->getUserId() !== null) {
                $userIds[] = (int) $panier->getUserId();
            }
        }
        $userIds = array_values(array_unique($userIds));

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userIds as $id) { $u = new class { public int $id; public function getId() { return $this->id; } public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $u->id = $id; $usersById[$id] = $u; }
        }

        $payments = [];
        $totalAmount = 0.0;
        $completed = 0;
        $pending = 0;
        $failed = 0;
        $methodCounts = [
            'CARTE' => 0,
            'PAYPAL' => 0,
            'VIREMENT' => 0,
        ];

        foreach ($paiements as $paiement) {
            $status = strtoupper((string) $paiement->getStatut());
            $method = strtoupper((string) $paiement->getMethodePaiement());
            $amount = (float) ($paiement->getMontantPaye() ?? 0);
            $panier = $paniersById[(int) $paiement->getPanierId()] ?? null;
            $user = $panier ? ($usersById[(int) $panier->getUserId()] ?? null) : null;

            $totalAmount += $amount;
            if ($status === 'VALIDE') {
                $completed++;
            }
            if ($status === 'EN_ATTENTE') {
                $pending++;
            }
            if ($status === 'ECHOUE') {
                $failed++;
            }

            if (array_key_exists($method, $methodCounts)) {
                $methodCounts[$method]++;
            }

            $payments[] = [
                'id' => (int) $paiement->getId(),
                'orderId' => (int) ($paiement->getPanierId() ?? 0),
                'customerName' => trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? ''))) ?: 'Unknown User',
                'amount' => $amount,
                'method' => $method ?: 'CARTE',
                'status' => $status ?: 'EN_ATTENTE',
                'date' => $paiement->getDatePaiement()?->format('Y-m-d H:i:s'),
                'transactionId' => $paiement->getReferenceTransaction() ?: ('PAY-' . $paiement->getId()),
            ];
        }

        $total = count($payments);

        $paymentsStatusChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $paymentsStatusChart->setData([
            'labels' => ['Completed', 'Pending', 'Failed'],
            'datasets' => [[
                'data' => [$completed, $pending, $failed],
                'backgroundColor' => ['#2ecc71', '#f1c40f', '#e74c3c'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
        ]);
        $paymentsStatusChart->setOptions([
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'title' => ['display' => true, 'text' => 'Payment Status'],
            ],
        ]);

        $paymentsMethodsChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $paymentsMethodsChart->setData([
            'labels' => ['Credit Card', 'PayPal', 'Bank Transfer'],
            'datasets' => [[
                'data' => [
                    $methodCounts['CARTE'] ?? 0,
                    $methodCounts['PAYPAL'] ?? 0,
                    $methodCounts['VIREMENT'] ?? 0,
                ],
                'backgroundColor' => ['#f7941d', '#1a3a5c', '#11998e'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
        ]);
        $paymentsMethodsChart->setOptions([
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'title' => ['display' => true, 'text' => 'Payment Methods'],
            ],
        ]);

        return $this->render('admin/payments_dashboard.html.twig', [
            'payments' => $payments,
            'paymentsStatusChart' => $paymentsStatusChart,
            'paymentsMethodsChart' => $paymentsMethodsChart,
            'stats' => [
                'totalAmount' => $totalAmount,
                'completed' => $completed,
                'pending' => $pending,
                'failed' => $failed,
                'successRate' => $total > 0 ? round(($completed * 100) / $total, 1) : 0,
                'failureRate' => $total > 0 ? round(($failed * 100) / $total, 1) : 0,
                'totalRows' => $total,
                'methods' => $methodCounts,
            ],
        ]);
    }

    #[Route('/feedbacks', name: 'feedbacks_dashboard', methods: ['GET'])]
    public function feedbacks(EntityManagerInterface $entityManager, HttpClientInterface $httpClient): Response
    {
        $connection = $entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $apiNinjasKey = $this->resolveApiNinjasKey();

        $rows = [];
        if ($schemaManager->tablesExist(['feedback'])) {
            $rows = $connection->fetchAllAssociative('SELECT id, user_id, message, created_at FROM feedback ORDER BY id DESC');
        }

        $userIds = array_values(array_unique(array_filter(array_map(
            static fn(array $row): int => (int) ($row['user_id'] ?? 0),
            $rows
        ))));

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userIds as $id) { $u = new class { public int $id; public function getId() { return $this->id; } public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $u->id = $id; $usersById[$id] = $u; }
        }

        $feedbacks = [];
        $todayDate = (new \DateTime())->format('Y-m-d');
        $todayCount = 0;
        $sentimentCache = [];

        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $user = $usersById[$userId] ?? null;
            $message = (string) ($row['message'] ?? '');

            $createdAtValue = $row['created_at'] ?? null;
            $createdAt = null;
            if ($createdAtValue instanceof \DateTimeInterface) {
                $createdAt = $createdAtValue->format('Y-m-d H:i:s');
            } elseif (is_string($createdAtValue) && trim($createdAtValue) !== '') {
                try {
                    $createdAt = (new \DateTime($createdAtValue))->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $createdAt = null;
                }
            }

            if ($createdAt !== null && str_starts_with($createdAt, $todayDate)) {
                $todayCount++;
            }

            $fullName = trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? '')));

            if (!array_key_exists($message, $sentimentCache)) {
                $sentimentCache[$message] = $this->analyzeFeedbackSentiment($httpClient, $message, $apiNinjasKey);
            }
            $sentiment = $sentimentCache[$message];

            $feedbacks[] = [
                'id' => (int) ($row['id'] ?? 0),
                'userId' => $userId,
                'userName' => $fullName !== '' ? $fullName : ('User #' . $userId),
                'email' => $user?->getEmail() ?? 'N/A',
                'message' => $message,
                'createdAt' => $createdAt,
                'sentimentLabel' => $sentiment['label'],
                'sentimentKey' => $sentiment['key'],
                'sentimentScore' => $sentiment['score'],
            ];
        }

        return $this->render('admin/feedbacks_dashboard.html.twig', [
            'feedbacks' => $feedbacks,
            'stats' => [
                'total' => count($feedbacks),
                'today' => $todayCount,
            ],
        ]);
    }

    #[Route('/feedbacks/{id}/send-mail', name: 'feedbacks_send_mail', methods: ['POST'])]
    public function sendFeedbackMail(int $id, Request $request, EntityManagerInterface $entityManager, HttpClientInterface $httpClient, MailerInterface $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('send_feedback_mail_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token for feedback email.');

            return $this->redirectToRoute('admin_feedbacks_dashboard');
        }

        $connection = $entityManager->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT id, user_id, message FROM feedback WHERE id = :id',
            ['id' => $id]
        );

        if (!is_array($row)) {
            $this->addFlash('error', 'Feedback not found.');

            return $this->redirectToRoute('admin_feedbacks_dashboard');
        }

        $userId = (int) ($row['user_id'] ?? 0);
        $message = trim((string) ($row['message'] ?? ''));
        $user = new class { public int $id; public function getPrenom() { return "Client"; } public function getNom() { return "#".$this->id; } public function getEmail() { return "client".$this->id."@explode.com"; } }; $user->id = $userId;
        $recipientEmail = 'adembenali2004@gmail.com';

        $customerName = trim((string) (($user?->getPrenom() ?? '') . ' ' . ($user?->getNom() ?? '')));
        if ($customerName === '') {
            $customerName = 'Customer';
        }

        $groqApiKey = $this->resolveGroqApiKey();
        if ($groqApiKey === '') {
            $this->addFlash('error', 'GROQ_API_KEY is missing. Configure it in your environment to generate replies.');
            return $this->redirectToRoute('admin_feedbacks_dashboard');
        }

        $reply = $this->generateGroqFeedbackReply($httpClient, $message, $customerName, $groqApiKey);
        if ($reply === null || trim($reply) === '') {
            $this->addFlash('error', 'Auto-generated reply failed. Please try again in a moment.');

            return $this->redirectToRoute('admin_feedbacks_dashboard');
        }

        $htmlReply = $this->buildFeedbackEmailHtml($customerName, $message, $reply);

        try {
            $email = (new Email())
                ->from('adembenali2004@gmail.com')
                ->to($recipientEmail)
                ->subject('Explora Support Response')
                ->text($reply)
                ->html($htmlReply);

            $mailer->send($email);

            $this->addFlash('success', sprintf('Auto-generated support email sent to %s.', $recipientEmail));
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Email sending failed: ' . $exception->getMessage());
        }

        return $this->redirectToRoute('admin_feedbacks_dashboard');
    }

    private function analyzeFeedbackSentiment(HttpClientInterface $httpClient, string $message, string $apiNinjasKey): array
    {
        if ($apiNinjasKey === '' || trim($message) === '') {
            return ['label' => 'Unknown', 'key' => 'unknown', 'score' => null];
        }

        try {
            $response = $httpClient->request('GET', 'https://api.api-ninjas.com/v1/sentiment', [
                'headers' => [
                    'X-Api-Key' => $apiNinjasKey,
                ],
                'query' => [
                    'text' => mb_substr($message, 0, 1000),
                ],
                'timeout' => 8,
            ]);

            $data = $response->toArray(false);
            $score = null;

            if (isset($data['score']) && is_numeric($data['score'])) {
                $score = (float) $data['score'];
            } elseif (isset($data[0]['score']) && is_numeric($data[0]['score'])) {
                $score = (float) $data[0]['score'];
            }

            if ($score === null) {
                $sentimentName = null;

                if (isset($data['sentiment']) && is_string($data['sentiment'])) {
                    $sentimentName = strtolower(trim($data['sentiment']));
                } elseif (isset($data[0]['sentiment']) && is_string($data[0]['sentiment'])) {
                    $sentimentName = strtolower(trim($data[0]['sentiment']));
                }

                if ($sentimentName === 'positive') {
                    return ['label' => 'Positive', 'key' => 'positive', 'score' => null];
                }
                if ($sentimentName === 'negative') {
                    return ['label' => 'Negative', 'key' => 'negative', 'score' => null];
                }
                if ($sentimentName === 'neutral') {
                    return ['label' => 'Neutral', 'key' => 'neutral', 'score' => null];
                }
            }

            if ($score === null) {
                return ['label' => 'Unknown', 'key' => 'unknown', 'score' => null];
            }

            if ($score >= 0.2) {
                return ['label' => 'Positive', 'key' => 'positive', 'score' => $score];
            }
            if ($score <= -0.2) {
                return ['label' => 'Negative', 'key' => 'negative', 'score' => $score];
            }

            return ['label' => 'Neutral', 'key' => 'neutral', 'score' => $score];
        } catch (\Throwable) {
            return ['label' => 'Unknown', 'key' => 'unknown', 'score' => null];
        }
    }

    private function resolveApiNinjasKey(): string
    {
        $candidates = [
            $_ENV['API_NINJAS_KEY'] ?? null,
            $_SERVER['API_NINJAS_KEY'] ?? null,
            getenv('API_NINJAS_KEY') ?: null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $value = trim($candidate);
            if ($value === '') {
                continue;
            }

            return trim($value, "\"'");
        }

        return '';
    }

    private function resolveGroqApiKey(): string
    {
        $candidates = [
            $_ENV['GROQ_API_KEY'] ?? null,
            $_SERVER['GROQ_API_KEY'] ?? null,
            getenv('GROQ_API_KEY') ?: null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $value = trim($candidate);
            if ($value === '') {
                continue;
            }

            return trim($value, "\"'");
        }

        return '';
    }

    private function generateGroqFeedbackReply(HttpClientInterface $httpClient, string $feedbackMessage, string $customerName, string $groqApiKey): ?string
    {
        if ($groqApiKey === '' || trim($feedbackMessage) === '') {
            return null;
        }

        $prompt = "You are Explora customer support. Write one concise, polite email response to this customer feedback. "
            . "Use plain text only, under 130 words. Address the customer as '{$customerName}'. "
            . "The reply must clearly reference at least one concrete detail from the feedback so it feels dedicated and not generic. "
            . "Do not say the response is autogenerated. "
            . "Customer feedback: {$feedbackMessage}";

        try {
            $response = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful customer support assistant for Explora.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 220,
                ],
                'timeout' => 12,
            ]);

            $data = $response->toArray(false);
            $text = $data['choices'][0]['message']['content'] ?? null;

            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function buildFeedbackEmailHtml(string $customerName, string $feedbackMessage, string $reply): string
    {
        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $safeFeedback = nl2br(htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8'));
        $safeReply = nl2br(htmlspecialchars($reply, ENT_QUOTES, 'UTF-8'));

        return '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#eef4fb;font-family:Segoe UI,Arial,sans-serif;color:#15314f;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef4fb;padding:24px 0;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="680" cellspacing="0" cellpadding="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 26px rgba(19,46,78,.14);">'
            . '<tr><td style="background:linear-gradient(120deg,#0f2943,#1f5b8f);padding:22px 28px;color:#ffffff;">'
            . '<div style="font-size:30px;font-weight:800;letter-spacing:1px;">EXPLORA</div>'
            . '<div style="opacity:.9;font-size:13px;margin-top:4px;">Customer Care Response</div>'
            . '</td></tr>'
            . '<tr><td style="padding:28px;">'
            . '<h2 style="margin:0 0 14px;font-size:24px;color:#15314f;">Hello ' . $safeName . ',</h2>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3a5876;">Thank you for sharing your feedback with us. Here is our response:</p>'
            . '<div style="background:#f5f9ff;border:1px solid #d8e8fb;border-radius:12px;padding:16px 18px;line-height:1.7;font-size:15px;color:#193a5b;">' . $safeReply . '</div>'
            . '<div style="margin-top:18px;padding:14px 16px;border-left:4px solid #f7941d;background:#fff9f2;border-radius:8px;">'
            . '<div style="font-weight:700;color:#8b5a15;margin-bottom:6px;">Your original feedback</div>'
            . '<div style="font-size:14px;line-height:1.6;color:#6a4f28;">' . $safeFeedback . '</div>'
            . '</div>'
            . '<p style="margin:18px 0 0;font-size:15px;line-height:1.7;color:#3a5876;">Best regards,<br><strong style="color:#15314f;">Explora Support Team</strong></p>'
            . '</td></tr>'
            . '<tr><td style="padding:16px 28px 24px;border-top:1px solid #e8eef6;color:#6b829d;font-size:12px;line-height:1.6;">'
            . 'This email was generated automatically from your recent feedback submission. If you need more help, simply reply to this message.'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</body></html>';
    }

    private function buildCsvDownloadResponse(string $fileName, array $headers, array $rows): Response
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return new Response('Unable to generate export file.', 500);
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers);

        foreach ($rows as $row) {
            $normalized = array_map(static function (mixed $value): string {
                if ($value === null) {
                    return '';
                }

                return (string) $value;
            }, $row);

            fputcsv($stream, $normalized);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        if (!is_string($content)) {
            return new Response('Unable to generate export file.', 500);
        }

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName));

        return $response;
    }

    #[Route('/payments/{id}/update', name: 'payments_update', methods: ['POST'])]
    public function updatePayment(Paiement $paiement, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $status = strtoupper(trim((string) $request->request->get('status', '')));
        $method = strtoupper(trim((string) $request->request->get('method', '')));

        $allowedStatuses = ['VALIDE', 'EN_ATTENTE', 'ECHOUE', 'REMBOURSE'];
        $allowedMethods = ['CARTE', 'PAYPAL', 'VIREMENT'];

        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('error', 'Invalid payment status.');
            return $this->redirectToRoute('admin_payments_dashboard');
        }

        if (!in_array($method, $allowedMethods, true)) {
            $this->addFlash('error', 'Invalid payment method.');
            return $this->redirectToRoute('admin_payments_dashboard');
        }

        $paiement->setStatut($status);
        $paiement->setMethodePaiement($method);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Payment %d updated.', (int) $paiement->getId()));

        return $this->redirectToRoute('admin_payments_dashboard');
    }
}