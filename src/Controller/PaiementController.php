<?php

namespace App\Controller;

use App\Entity\Hebergement;
use App\Entity\Paiement;
use App\Entity\Panier;
use App\Entity\ProduitPanier;
use App\Entity\PromoCode;
use App\Entity\SavedCards;
use App\Entity\Transport;
use App\Entity\Voyage;
use App\Service\PaymentMailerService;
use App\Service\StripePaymentService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/paiements')]
class PaiementController extends AbstractController
{
    private const CURRENT_USER_ID = 1;

    #[Route('', name: 'app_paiement_index', methods: ['GET'])]
    #[Route('', name: 'payment', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);

        return $this->render('paiement/payment.html.twig', array_merge([
            'paiements' => $this->getPaiementsForCurrentUser($entityManager),
        ], $this->buildPaymentTemplateData($entityManager, $panier)));
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET'])]
    public function new(EntityManagerInterface $entityManager): Response
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);

        return $this->render('paiement/payment.html.twig', array_merge([
            'paiements' => $this->getPaiementsForCurrentUser($entityManager),
        ], $this->buildPaymentTemplateData($entityManager, $panier)));
    }

    #[Route('/process', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, EntityManagerInterface $entityManager, PaymentMailerService $paymentMailerService, ValidatorInterface $validator, StripePaymentService $stripePaymentService): Response
    {
        if (!$this->isCsrfTokenValid('payment', (string) $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Token CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_paiement_index');
        }

        if (!$request->request->has('terms')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Vous devez accepter les conditions pour continuer.'], 400);
            }
            $this->addFlash('error', 'Vous devez accepter les conditions pour continuer.');
            return $this->redirectToRoute('app_paiement_index');
        }

        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $cartItems = $this->getCartItems($entityManager, $panier);

        if ($cartItems === []) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Votre panier est vide.'], 400);
            }
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        if (strtoupper((string) $panier->getStatut()) !== 'ACTIF') {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Ce panier est deja valide.'], 400);
            }
            $this->addFlash('error', 'Ce panier est deja valide.');
            return $this->redirectToRoute('app_panier_index');
        }

        $methodInput = strtolower((string) $request->request->get('payment_method', 'credit'));
        $methodePaiement = $methodInput === 'paypal' ? 'PAYPAL' : 'CARTE';

        $validationErrors = $this->validateForm($request, $methodePaiement, $validator);
        if ($validationErrors !== []) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => $validationErrors[0] ?? 'Validation error.'], 400);
            }
            foreach ($validationErrors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('app_paiement_index');
        }

        $transactionReference = $this->generateTransactionReference();
        $cardDigits = preg_replace('/\D+/', '', (string) $request->request->get('card_number', '')) ?? '';
        $cardBrand = $this->detectCardType($cardDigits);
        if ($methodePaiement === 'CARTE') {
            if ($cardBrand === null) {
                $this->addFlash('error', 'Unsupported card type.');
                return $this->redirectToRoute('app_paiement_index');
            }

            $billingStreet = trim((string) $request->request->get('street', ''));
            $billingCity = trim((string) $request->request->get('city', ''));
            $billingCountry = trim((string) $request->request->get('country', ''));
            $billingPostal = trim((string) $request->request->get('postal_code', ''));

            $metadata = [
                'billing_city' => $billingCity,
                'billing_country' => $billingCountry,
                'billing_postal_code' => $billingPostal,
                'billing_street' => $billingStreet,
                'card_brand' => ucfirst($cardBrand),
                'card_exp_month' => sprintf('%02d', (int) $request->request->get('expiry_month', 0)),
                'card_exp_year' => (string) (int) $request->request->get('expiry_year', 0),
                'card_last4' => substr($cardDigits, -4),
                'cardholder_name' => trim((string) $request->request->get('cardholder_name', '')),
            ];

            try {
                $stripeResult = $stripePaymentService->chargeCard(
                    (float) ($panier->getMontantTtc() ?? 0),
                    'USD',
                    $cardBrand,
                    $cardDigits,
                    trim((string) $request->request->get('cardholder_name', '')),
                    sprintf('Explora booking payment for panier #%d', (int) $panier->getId()),
                    $metadata
                );

                if (($stripeResult['status'] ?? '') !== 'succeeded') {
                    $this->addFlash('error', 'Stripe payment was not completed. Status: ' . ($stripeResult['status'] ?? 'unknown'));
                    return $this->redirectToRoute('app_paiement_index');
                }

                $transactionReference = (string) ($stripeResult['id'] ?? $transactionReference);

                if ($request->request->has('save_card')) {
                    $savedCard = new SavedCards();
                    $savedCard->setUserId(self::CURRENT_USER_ID);
                    $savedCard->setCardholderName(trim((string) $request->request->get('cardholder_name', '')));
                    $savedCard->setCardBrand(ucfirst($cardBrand));
                    $savedCard->setLastFourDigits(substr($cardDigits, -4));
                    $savedCard->setExpiryMonth((int) $request->request->get('expiry_month', 0));
                    $savedCard->setExpiryYear((int) $request->request->get('expiry_year', 0));
                    $savedCard->setBillingAddress($billingStreet);
                    $savedCard->setBillingCity($billingCity);
                    $savedCard->setBillingCountry($billingCountry);
                    $savedCard->setBillingPostalCode($billingPostal);
                    $savedCard->setStripePaymentMethodId((string) ($stripeResult['payment_method'] ?? null));
                    $savedCard->setIsDefault(false);
                    $savedCard->setCreatedAt(new \DateTime());
                    $savedCard->setUpdatedAt(new \DateTime());

                    $entityManager->persist($savedCard);
                }
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'Stripe payment failed: ' . $exception->getMessage());
                return $this->redirectToRoute('app_paiement_index');
            }
        }

        $paiement = new Paiement();
        $paiement->setPanierId((int) $panier->getId());
        $paiement->setMontantPaye(number_format((float) ($panier->getMontantTtc() ?? 0), 2, '.', ''));
        $paiement->setDevise('USD');
        $paiement->setMethodePaiement($methodePaiement);
        $paiement->setStatut('VALIDE');
        $paiement->setReferenceTransaction($transactionReference);
        $paiement->setDatePaiement(new \DateTime());
        $paiement->setTokenSecurise($this->resolveSecureToken($request, $methodePaiement));
        $paiement->setAdresseFacturation($this->buildBillingAddress($request));

        $panier->setStatut('VALIDE');
        $panier->setDateModification(new \DateTime());

        $entityManager->persist($paiement);
        $entityManager->flush();

        try {
            $paymentMailerService->sendPaymentInvoice($paiement, $panier, $cartItems);
            $this->addFlash('success', 'Email de confirmation envoye avec la facture PDF.');
        } catch (\Throwable $exception) {
            $this->addFlash('warning', 'Paiement valide, mais envoi d\'email echoue: ' . $exception->getMessage());
        }

        $scratchReward = $this->generateScratchReward($entityManager);
        $this->addFlash('scratch_reward', $scratchReward);
        $this->addFlash('success', sprintf('Paiement valide. Reference: %s', (string) $paiement->getReferenceTransaction()));

        return $this->redirectToRoute('app_paiement_index');
    }

    #[Route('/feedback', name: 'payment_feedback_store', methods: ['POST'])]
    public function storeFeedback(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Invalid request.'], 400);
        }

        if (!$this->isCsrfTokenValid('payment', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide.'], 400);
        }

        $message = trim((string) $request->request->get('message', ''));
        if ($message === '' || mb_strlen($message) < 3) {
            return new JsonResponse(['error' => 'Feedback message must contain at least 3 characters.'], 400);
        }
        if (mb_strlen($message) > 2000) {
            return new JsonResponse(['error' => 'Feedback message is too long.'], 400);
        }

        try {
            $connection = $entityManager->getConnection();
            $this->ensureFeedbackTableExists($connection);

            $connection->insert('feedback', [
                'user_id' => self::CURRENT_USER_ID,
                'message' => $message,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => 'Failed to save feedback: ' . $exception->getMessage()], 500);
        }
    }

    #[Route('/history', name: 'api_purchase_history', methods: ['GET'])]
    public function purchaseHistory(EntityManagerInterface $entityManager): JsonResponse
    {
        $paiements = $this->getPaiementsForCurrentUser($entityManager);

        $rows = array_map(static function (Paiement $p): array {
            return [
                'id' => $p->getId(),
                'reference' => $p->getReferenceTransaction(),
                'amount' => (float) ($p->getMontantPaye() ?? 0),
                'method' => $p->getMethodePaiement(),
                'status' => $p->getStatut(),
                'panierId' => $p->getPanierId(),
                'cardToken' => $p->getTokenSecurise(),
                'paidAt' => $p->getDatePaiement()?->format('Y-m-d H:i:s'),
            ];
        }, $paiements);

        return $this->json($rows);
    }

    private function getOrCreateCurrentPanier(EntityManagerInterface $entityManager): Panier
    {
        $panier = $entityManager->getRepository(Panier::class)->findOneBy([
            'userId' => self::CURRENT_USER_ID,
            'statut' => 'ACTIF',
        ], ['id' => 'DESC']);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUserId(self::CURRENT_USER_ID);
            $panier->setStatut('ACTIF');
            $panier->setDateCreation(new \DateTime());
            $panier->setDateModification(new \DateTime());
            $panier->setMontantTotalHt('0.00');
            $panier->setMontantTva('0.00');
            $panier->setMontantReduction('0.00');
            $panier->setMontantTtc('0.00');
            $entityManager->persist($panier);
            $entityManager->flush();
        }

        return $panier;
    }

    private function getPaiementsForCurrentUser(EntityManagerInterface $entityManager): array
    {
        $paniers = $entityManager->getRepository(Panier::class)->findBy([
            'userId' => self::CURRENT_USER_ID,
        ]);

        if ($paniers === []) {
            return [];
        }

        $panierIds = array_values(array_filter(array_map(static fn(Panier $panier): ?int => $panier->getId(), $paniers)));
        if ($panierIds === []) {
            return [];
        }

        return $entityManager->getRepository(Paiement::class)->findBy([
            'panierId' => $panierIds,
        ], ['id' => 'DESC']);
    }

    private function getCartItems(EntityManagerInterface $entityManager, Panier $panier): array
    {
        $allItems = $entityManager->getRepository(ProduitPanier::class)->findBy([
            'panierId' => $panier->getId(),
        ], ['id' => 'DESC']);

        // Load referenced entities by type
        $hebergementIds = [];
        $transportIds = [];
        $voyageIds = [];
        $activiteIds = [];

        foreach ($allItems as $item) {
            $type = strtoupper((string) $item->getTypeProduit());
            $produitId = (int) ($item->getProduitId() ?? 0);
            if ($type === 'HEBERGEMENT') {
                $hebergementIds[] = $produitId;
            } elseif ($type === 'TRANSPORT') {
                $transportIds[] = $produitId;
            } elseif ($type === 'VOYAGE') {
                $voyageIds[] = $produitId;
            } elseif ($type === 'ACTIVITE') {
                $activiteIds[] = $produitId;
            }
        }

        $hebergements = [];
        if ($hebergementIds !== []) {
            foreach ($entityManager->getRepository(Hebergement::class)->findBy(['id' => array_unique($hebergementIds)]) as $h) {
                $hebergements[(int) $h->getId()] = $h;
            }
        }
        $transports = [];
        if ($transportIds !== []) {
            foreach ($entityManager->getRepository(Transport::class)->findBy(['id' => array_unique($transportIds)]) as $t) {
                $transports[(int) $t->getId()] = $t;
            }
        }
        $voyages = [];
        if ($voyageIds !== []) {
            foreach ($entityManager->getRepository(Voyage::class)->findBy(['idVoyage' => array_unique($voyageIds)]) as $v) {
                $voyages[(int) $v->getId()] = $v;
            }
        }
        $activites = [];
        if ($activiteIds !== []) {
            foreach ($entityManager->getRepository(\App\Entity\Activite::class)->findBy(['idActivite' => array_unique($activiteIds)]) as $a) {
                $activites[(int) $a->getIdActivite()] = $a;
            }
        }

        $result = [];
        foreach ($allItems as $item) {
            $type = strtoupper((string) $item->getTypeProduit());
            $produitId = (int) ($item->getProduitId() ?? 0);

            $name = 'Item';
            if ($type === 'HEBERGEMENT') {
                $h = $hebergements[$produitId] ?? null;
                $name = $h ? $h->getNom() : 'Hebergement #' . $produitId;
            } elseif ($type === 'TRANSPORT') {
                $t = $transports[$produitId] ?? null;
                $name = $t ? ($t->getOrigine() . ' → ' . $t->getDestination()) : 'Transport #' . $produitId;
            } elseif ($type === 'VOYAGE') {
                $v = $voyages[$produitId] ?? null;
                $name = $v ? ($v->getNom() ?? 'Voyage') : 'Voyage #' . $produitId;
            } elseif ($type === 'ACTIVITE') {
                $a = $activites[$produitId] ?? null;
                $name = $a ? $a->getNom() : 'Activité #' . $produitId;
            }

            $result[] = [
                'id' => (int) $item->getId(),
                'name' => $name,
                'typeProduit' => $type,
                'quantity' => (int) ($item->getQuantite() ?? 1),
                'pricePerPerson' => (float) ($item->getPrixUnitaire() ?? 0),
            ];
        }

        return $result;
    }

    private function buildPaymentTemplateData(EntityManagerInterface $entityManager, Panier $panier): array
    {
        $cartItems = $this->getCartItems($entityManager, $panier);
        $subtotal = (float) ($panier->getMontantTotalHt() ?? 0);
        $taxes = (float) ($panier->getMontantTva() ?? 0);
        $discount = (float) ($panier->getMontantReduction() ?? 0);
        $total = (float) ($panier->getMontantTtc() ?? 0);

        $discountPercent = 0;
        $promoCode = $panier->getCodePromo();
        if ($promoCode !== null) {
            $promo = $entityManager->getRepository(PromoCode::class)->findOneBy([
                'code' => $promoCode,
                'userId' => self::CURRENT_USER_ID,
            ]);

            if ($promo) {
                $discountPercent = (int) ($promo->getDiscountPercent() ?? 0);
            }
        }

        return [
            'countries' => [
                ['code' => 'TN', 'name' => 'Tunisia'],
                ['code' => 'FR', 'name' => 'France'],
                ['code' => 'IT', 'name' => 'Italy'],
                ['code' => 'ES', 'name' => 'Spain'],
            ],
            'cities' => [
                ['code' => 'TUN', 'name' => 'Tunis'],
                ['code' => 'PAR', 'name' => 'Paris'],
                ['code' => 'ROM', 'name' => 'Rome'],
                ['code' => 'MAD', 'name' => 'Madrid'],
            ],
            'billingCountry' => null,
            'billingCity' => null,
            'billingStreet' => null,
            'billingPostal' => null,
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'taxes' => $taxes,
            'discount' => $discount,
            'promoCode' => $promoCode,
            'discountPercent' => $discountPercent,
            'total' => $total,
        ];
    }

    private function buildBillingAddress(Request $request): ?string
    {
        $parts = array_filter([
            $this->nullableString($request->request->get('street')),
            $this->nullableString($request->request->get('city')),
            $this->nullableString($request->request->get('postal_code')),
            $this->nullableString($request->request->get('country')),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    private function generateTransactionReference(): string
    {
        return sprintf('PAY-%s-%s', strtoupper(substr(bin2hex(random_bytes(6)), 0, 8)), (string) time());
    }

    private function validateForm(Request $request, string $methodePaiement, ValidatorInterface $validator): array
    {
        $addressData = [
            'country' => trim((string) $request->request->get('country', '')),
            'city' => trim((string) $request->request->get('city', '')),
            'street' => trim((string) $request->request->get('street', '')),
            'postal_code' => trim((string) $request->request->get('postal_code', '')),
        ];

        $addressConstraints = new Assert\Collection([
            'allowExtraFields' => true,
            'fields' => [
                'country' => [new Assert\NotBlank(message: 'Country is required.')],
                'city' => [new Assert\NotBlank(message: 'City is required.')],
                'street' => [
                    new Assert\NotBlank(message: 'Street address must contain at least 5 characters.'),
                    new Assert\Length(min: 5, minMessage: 'Street address must contain at least 5 characters.'),
                ],
                'postal_code' => [
                    new Assert\NotBlank(message: 'Postal code format is invalid.'),
                    new Assert\Regex(pattern: '/^[A-Za-z0-9\-\s]{3,12}$/', message: 'Postal code format is invalid.'),
                ],
            ],
        ]);

        $errors = $this->collectValidationErrors($validator->validate($addressData, $addressConstraints));

        if ($methodePaiement !== 'CARTE') {
            return $errors;
        }

        $cardholder = trim((string) $request->request->get('cardholder_name', ''));
        $cardNumberRaw = (string) $request->request->get('card_number', '');
        $cardDigits = preg_replace('/\D+/', '', $cardNumberRaw) ?? '';
        $expiryMonth = (int) $request->request->get('expiry_month', 0);
        $expiryYear = (int) $request->request->get('expiry_year', 0);

        $cardData = [
            'cardholder_name' => $cardholder,
            'cvv' => trim((string) $request->request->get('cvv', '')),
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
        ];

        $cardConstraints = new Assert\Collection([
            'allowExtraFields' => true,
            'fields' => [
                'cardholder_name' => [
                    new Assert\NotBlank(message: 'Cardholder name is invalid.'),
                    new Assert\Regex(pattern: '/^[\p{L}\s\'\-]{2,80}$/u', message: 'Cardholder name is invalid.'),
                ],
                'cvv' => [
                    new Assert\NotBlank(message: 'CVV must contain 3 or 4 digits.'),
                    new Assert\Regex(pattern: '/^\d{3,4}$/', message: 'CVV must contain 3 or 4 digits.'),
                ],
                'expiry_month' => [new Assert\Range(min: 1, max: 12, notInRangeMessage: 'Expiry date is invalid.')],
                'expiry_year' => [new Assert\Range(min: 2000, notInRangeMessage: 'Expiry date is invalid.')],
            ],
        ]);

        $errors = array_merge($errors, $this->collectValidationErrors($validator->validate($cardData, $cardConstraints)));

        $cardType = $this->detectCardType($cardDigits);
        if ($cardType === null) {
            $errors[] = 'Unsupported card type.';
        } else {
            $expectedLengths = $this->expectedCardLengths($cardType);
            if (!in_array(strlen($cardDigits), $expectedLengths, true)) {
                $errors[] = 'Card number length is invalid.';
            }

            if (!$this->luhnCheck($cardDigits)) {
                $errors[] = 'Card number failed Luhn validation.';
            }
        }

        if ($expiryMonth >= 1 && $expiryMonth <= 12 && $expiryYear >= 2000) {
            $expiry = \DateTime::createFromFormat('Y-n-j H:i:s', sprintf('%d-%d-1 23:59:59', $expiryYear, $expiryMonth));
            if ($expiry instanceof \DateTime) {
                $expiry->modify('last day of this month');
                if ($expiry < new \DateTime()) {
                    $errors[] = 'Card is expired.';
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private function collectValidationErrors(iterable $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = (string) $violation->getMessage();
        }

        return $errors;
    }

    private function detectCardType(string $digits): ?string
    {
        if (preg_match('/^4/', $digits)) {
            return 'visa';
        }
        if (preg_match('/^(5[1-5]|2[2-7])/', $digits)) {
            return 'mastercard';
        }
        if (preg_match('/^3[47]/', $digits)) {
            return 'amex';
        }
        if (preg_match('/^(6011|65|64[4-9])/', $digits)) {
            return 'discover';
        }

        return null;
    }

    private function expectedCardLengths(string $cardType): array
    {
        return match ($cardType) {
            'amex' => [15],
            'visa' => [13, 16],
            'mastercard', 'discover' => [16],
            default => [],
        };
    }

    private function luhnCheck(string $digits): bool
    {
        if ($digits === '') {
            return false;
        }

        $sum = 0;
        $double = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }

    private function resolveSecureToken(Request $request, string $methodePaiement): ?string
    {
        if ($methodePaiement !== 'CARTE') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $request->request->get('card_number', ''));
        if ($digits === null || strlen($digits) < 4) {
            return null;
        }

        return '****' . substr($digits, -4);
    }

    private function generateScratchReward(EntityManagerInterface $entityManager): array
    {
        $possibleDiscounts = [0, 5, 10, 15, 20, 25, 30, 40, 50];
        $discount = $possibleDiscounts[array_rand($possibleDiscounts)];

        if ($discount <= 0) {
            return [
                'discount' => 0,
                'code' => null,
            ];
        }

        $promoCode = new PromoCode();
        $promoCode->setCode($this->generatePromoCodeValue());
        $promoCode->setCreatedAt(new \DateTime());
        $promoCode->setDiscountPercent($discount);
        $promoCode->setPanierId(null);
        $promoCode->setIsUsed(false);
        $promoCode->setUsedAt(null);
        $promoCode->setUserId(self::CURRENT_USER_ID);

        $entityManager->persist($promoCode);
        $entityManager->flush();

        return [
            'discount' => $discount,
            'code' => $promoCode->getCode(),
        ];
    }

    private function generatePromoCodeValue(): string
    {
        return 'SCRATCH-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    }

    private function nullableString(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }

    private function ensureFeedbackTableExists(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();
        if ($schemaManager->tablesExist(['feedback'])) {
            return;
        }

        $schema = new Schema();
        $table = $schema->createTable('feedback');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('message', 'text');
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(['id']);

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }
    }
}
