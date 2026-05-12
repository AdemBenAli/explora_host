<?php

namespace App\Controller;

use App\Entity\Hebergement;
use App\Entity\Panier;
use App\Entity\ProduitPanier;
use App\Entity\PromoCode;
use App\Entity\Transport;
use App\Entity\Voyage;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/paniers')]
class PanierController extends AbstractController
{
    private const CURRENT_USER_ID = 1;

    #[Route('', name: 'app_panier_index', methods: ['GET'])]
    #[Route('', name: 'cart', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);

        return $this->render('panier/cart.html.twig', array_merge([
            'paniers' => [$panier],
        ], $this->cartTemplateData($entityManager, $panier)));
    }

    #[Route('/apply-promo', name: 'cart_apply_promo', methods: ['POST'])]
    public function applyPromo(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $promoCode = strtoupper(trim((string) $request->request->get('promo_code', '')));

        if ($promoCode === '') {
            $panier->setCodePromo(null);
            $panier->setMontantReduction('0.00');
            $this->refreshPanierTotals($entityManager, $panier);
            $entityManager->flush();
            $this->addFlash('success', 'Code promo retire.');

            return $this->redirectToRoute('app_panier_index');
        }

        $promo = $entityManager->getRepository(PromoCode::class)->findOneBy([
            'code' => $promoCode,
            'userId' => self::CURRENT_USER_ID,
            'isUsed' => false,
        ]);

        if (!$promo) {
            $this->addFlash('error', 'Code promo invalide ou deja utilise.');
            return $this->redirectToRoute('app_panier_index');
        }

        $panier->setCodePromo($promo->getCode());

        $promo->setIsUsed(true);
        $promo->setUsedAt(new \DateTime());
        $promo->setPanierId((int) $panier->getId());

        $this->refreshPanierTotals($entityManager, $panier);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Code promo %s applique.', $promo->getCode()));

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/add-hebergement', name: 'cart_add_hebergement', methods: ['POST'])]
    public function addHebergementToCart(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $hebergementId = (int) $request->request->get('hebergement_id', 0);
        $hebergement = $entityManager->getRepository(Hebergement::class)->find($hebergementId);

        if (!$hebergement) {
            $this->addFlash('error', 'Hebergement introuvable.');
            return $this->redirectToRoute('app_hebergement_front');
        }

        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $produitRepo = $entityManager->getRepository(ProduitPanier::class);

        $item = $produitRepo->findOneBy([
            'panierId' => $panier->getId(),
            'produitId' => $hebergementId,
            'typeProduit' => 'HEBERGEMENT',
        ]);

        $prixUnitaire = (string) ($hebergement->getPrixParNuit() ?? 0);
        $qty = max(1, (int) $request->request->get('nights', 1));

        if (!$item) {
            $item = new ProduitPanier();
            $item->setPanierId((int) $panier->getId());
            $item->setProduitId($hebergementId);
            $item->setTypeProduit('HEBERGEMENT');
            $item->setDateAjout(new \DateTime());
            $item->setQuantite($qty);
            $item->setPrixUnitaire($prixUnitaire);
            $item->setPrixTotalLigne($this->multiplyDecimal($prixUnitaire, $qty));
            $entityManager->persist($item);
        } else {
            $newQty = max(1, (int) $item->getQuantite()) + $qty;
            $item->setQuantite($newQty);
            $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $newQty));
        }

        $entityManager->flush();
        $this->refreshPanierTotals($entityManager, $panier);
        $entityManager->flush();

        $this->addFlash('success', sprintf('"%s" ajoute au panier.', $hebergement->getNom()));

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/add-transport', name: 'cart_add_transport', methods: ['POST'])]
    public function addTransportToCart(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $transportId = (int) $request->request->get('transport_id', 0);
        $transport = $entityManager->getRepository(Transport::class)->find($transportId);

        if (!$transport) {
            $this->addFlash('error', 'Transport introuvable.');
            return $this->redirectToRoute('user_transport');
        }

        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $produitRepo = $entityManager->getRepository(ProduitPanier::class);

        $item = $produitRepo->findOneBy([
            'panierId' => $panier->getId(),
            'produitId' => $transportId,
            'typeProduit' => 'TRANSPORT',
        ]);

        $prixUnitaire = $transport->getPrix() ?? '0';
        $places = max(1, (int) $request->request->get('places', 1));

        if (!$item) {
            $item = new ProduitPanier();
            $item->setPanierId((int) $panier->getId());
            $item->setProduitId($transportId);
            $item->setTypeProduit('TRANSPORT');
            $item->setDateAjout(new \DateTime());
            $item->setQuantite($places);
            $item->setPrixUnitaire($prixUnitaire);
            $item->setPrixTotalLigne($this->multiplyDecimal($prixUnitaire, $places));
            $entityManager->persist($item);
        } else {
            $newQty = max(1, (int) $item->getQuantite()) + $places;
            $item->setQuantite($newQty);
            $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $newQty));
        }

        $entityManager->flush();
        $this->refreshPanierTotals($entityManager, $panier);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Transport %s → %s ajoute au panier.', $transport->getOrigine(), $transport->getDestination()));

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/add-voyage', name: 'cart_add_voyage', methods: ['POST'])]
    public function addVoyageToCart(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $voyageId = (int) $request->request->get('voyage_id', 0);
        $voyage = $entityManager->getRepository(Voyage::class)->find($voyageId);

        if (!$voyage) {
            $this->addFlash('error', 'Voyage introuvable.');
            return $this->redirectToRoute('app_voyage_index');
        }

        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $produitRepo = $entityManager->getRepository(ProduitPanier::class);

        $item = $produitRepo->findOneBy([
            'panierId' => $panier->getId(),
            'produitId' => $voyageId,
            'typeProduit' => 'VOYAGE',
        ]);

        $prixUnitaire = (string) ($voyage->getBudgetTotal() ?? 0);
        $qty = max(1, (int) $request->request->get('qty', 1));

        if (!$item) {
            $item = new ProduitPanier();
            $item->setPanierId((int) $panier->getId());
            $item->setProduitId($voyageId);
            $item->setTypeProduit('VOYAGE');
            $item->setDateAjout(new \DateTime());
            $item->setQuantite($qty);
            $item->setPrixUnitaire($prixUnitaire);
            $item->setPrixTotalLigne($this->multiplyDecimal($prixUnitaire, $qty));
            $entityManager->persist($item);
        } else {
            $newQty = max(1, (int) $item->getQuantite()) + $qty;
            $item->setQuantite($newQty);
            $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $newQty));
        }

        $entityManager->flush();
        $this->refreshPanierTotals($entityManager, $panier);
        $entityManager->flush();

        $this->addFlash('success', sprintf('"%s" ajoute au panier.', $voyage->getNom()));

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/add-activite', name: 'cart_add_activite', methods: ['POST'])]
    public function addActiviteToCart(Request $request, EntityManagerInterface $entityManager): JsonResponse|RedirectResponse
    {
        $activiteId = (int) $request->request->get('activite_id', 0);
        $activite = $entityManager->getRepository(\App\Entity\Activite::class)->find($activiteId);

        if (!$activite) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Activité introuvable.'], 404);
            }
            $this->addFlash('error', 'Activité introuvable.');
            return $this->redirectToRoute('voyageur_index');
        }

        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $produitRepo = $entityManager->getRepository(ProduitPanier::class);

        $qty = max(1, (int) $request->request->get('nombrePlaces', 1));
        $prixUnitaire = (string) ($activite->getPrix() ?? 0);

        $item = $produitRepo->findOneBy([
            'panierId'    => $panier->getId(),
            'produitId'   => $activiteId,
            'typeProduit' => 'ACTIVITE',
        ]);

        if (!$item) {
            $item = new ProduitPanier();
            $item->setPanierId((int) $panier->getId());
            $item->setProduitId($activiteId);
            $item->setTypeProduit('ACTIVITE');
            $item->setDateAjout(new \DateTime());
            $item->setQuantite($qty);
            $item->setPrixUnitaire($prixUnitaire);
            $item->setPrixTotalLigne($this->multiplyDecimal($prixUnitaire, $qty));
            $entityManager->persist($item);
        } else {
            $newQty = max(1, (int) $item->getQuantite()) + $qty;
            $item->setQuantite($newQty);
            $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $newQty));
        }

        $entityManager->flush();
        $this->refreshPanierTotals($entityManager, $panier);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            $count = count($produitRepo->findBy(['panierId' => $panier->getId()]));
            return $this->json(['success' => true, 'nom' => $activite->getNom(), 'count' => $count]);
        }

        $this->addFlash('success', sprintf('"%s" ajouté au panier.', $activite->getNom()));
        return $this->redirectToRoute('voyageur_index');
    }

    #[Route('/increase/{id}', name: 'cart_increase', methods: ['POST'])]
    public function increase(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $item = $entityManager->getRepository(ProduitPanier::class)->find($id);

        if ($item && (int) $item->getPanierId() === (int) $panier->getId()) {
            $qty = max(1, (int) $item->getQuantite()) + 1;
            $item->setQuantite($qty);
            $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $qty));
            $entityManager->flush();
            $this->refreshPanierTotals($entityManager, $panier);
            $entityManager->flush();
            $this->addFlash('success', 'Quantite augmentee.');
        }

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/decrease/{id}', name: 'cart_decrease', methods: ['POST'])]
    public function decrease(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $item = $entityManager->getRepository(ProduitPanier::class)->find($id);

        if ($item && (int) $item->getPanierId() === (int) $panier->getId()) {
            $qty = (int) ($item->getQuantite() ?? 1);
            if ($qty <= 1) {
                $entityManager->remove($item);
            } else {
                $qty--;
                $item->setQuantite($qty);
                $item->setPrixTotalLigne($this->multiplyDecimal((string) ($item->getPrixUnitaire() ?? '0'), $qty));
            }
            $entityManager->flush();
            $this->refreshPanierTotals($entityManager, $panier);
            $entityManager->flush();
            $this->addFlash('success', 'Quantite mise a jour.');
        }

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $panier = $this->getOrCreateCurrentPanier($entityManager);
        $item = $entityManager->getRepository(ProduitPanier::class)->find($id);

        if ($item && (int) $item->getPanierId() === (int) $panier->getId()) {
            $entityManager->remove($item);
            $entityManager->flush();
            $this->refreshPanierTotals($entityManager, $panier);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprime du panier.');
        }

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/ai-recommendation', name: 'cart_ai_recommendation', methods: ['GET'])]
    public function aiRecommendation(EntityManagerInterface $entityManager, GeminiService $geminiService): JsonResponse
    {
        try {
            $panier = $this->getOrCreateCurrentPanier($entityManager);
            $data = $this->cartTemplateData($entityManager, $panier);
            $cartItems = $data['cartItems'] ?? [];

            if (!is_array($cartItems) || $cartItems === []) {
                return $this->json(['error' => 'Your cart is empty. Add items first.'], 400);
            }

            $prompt = $this->buildGeminiPrompt($cartItems, (float) ($data['total'] ?? 0));
            $recommendation = $geminiService->generateContent($prompt);

            return $this->json([
                'recommendation' => $recommendation,
            ]);
        } catch (\Throwable $exception) {
            return $this->json([
                'error' => 'AI Recommendation failed: ' . $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('/reminder/ping', name: 'cart_reminder_ping', methods: ['GET'])]
    public function reminderPing(EntityManagerInterface $entityManager): JsonResponse
    {
        $panier = $entityManager->getRepository(Panier::class)->findOneBy([
            'userId' => self::CURRENT_USER_ID,
            'statut' => 'ACTIF',
        ], ['id' => 'DESC']);

        if (!$panier) {
            return $this->json(['published' => false, 'reason' => 'no_active_cart']);
        }

        $items = $entityManager->getRepository(ProduitPanier::class)->findBy([
            'panierId' => $panier->getId(),
        ]);

        if ($items === []) {
            return $this->json(['published' => false, 'reason' => 'empty_cart']);
        }

        $notification = (new Notification('Cart Reminder', ['browser']))
            ->content('You have items waiting in your cart. Complete your purchase now!');

        return $this->json([
            'published' => true,
            'title' => $notification->getSubject(),
            'message' => $notification->getContent(),
            'timestamp' => (new \DateTime())->format(DATE_ATOM),
        ]);
    }

    #[Route('/count', name: 'cart_count', methods: ['GET'])]
    public function cartCount(EntityManagerInterface $entityManager): JsonResponse
    {
        $panier = $entityManager->getRepository(Panier::class)->findOneBy([
            'userId' => self::CURRENT_USER_ID,
            'statut' => 'ACTIF',
        ], ['id' => 'DESC']);

        $count = 0;
        if ($panier) {
            $count = count($entityManager->getRepository(ProduitPanier::class)->findBy([
                'panierId' => $panier->getId(),
            ]));
        }

        return $this->json(['count' => $count]);
    }

    private function buildGeminiPrompt(array $cartItems, float $total): string
    {
        $lines = [];
        $lines[] = 'You are a senior travel advisor AI for a travel booking app called Explora.';
        $lines[] = 'Analyze the following cart items and provide smart travel recommendations.';
        $lines[] = '';
        $lines[] = '=== CART ITEMS ===';

        foreach ($cartItems as $item) {
            $lines[] = sprintf(
                '- %s: %s | Price: $%s | Qty: %d',
                (string) ($item['typeProduit'] ?? 'Item'),
                (string) ($item['name'] ?? 'Unknown'),
                number_format((float) ($item['pricePerPerson'] ?? 0), 2, '.', ''),
                (int) ($item['quantity'] ?? 1)
            );
        }

        $lines[] = '';
        $lines[] = '=== TOTAL: $' . number_format($total, 2, '.', '') . ' ===';
        $lines[] = '';
        $lines[] = 'RULES: Be concise. Max 60 lines total. Use plain text only, no markdown.';
        $lines[] = 'Use emojis as bullets. Keep each point to 1 line.';
        $lines[] = '';
        $lines[] = 'Provide exactly these sections:';
        $lines[] = '🌍 DESTINATION INTELLIGENCE';
        $lines[] = '✈️ UPGRADE SUGGESTIONS';
        $lines[] = '🎯 SMART TIPS';

        return implode("\n", $lines);
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
            $panier->setMontantTotalHt('0');
            $panier->setMontantTva('0');
            $panier->setMontantTtc('0');
            $panier->setMontantReduction('0');

            $entityManager->persist($panier);
            $entityManager->flush();
        }

        return $panier;
    }

    private function cartTemplateData(EntityManagerInterface $entityManager, Panier $panier): array
    {
        $allItems = $entityManager->getRepository(ProduitPanier::class)->findBy([
            'panierId' => $panier->getId(),
        ], ['id' => 'DESC']);

        // Load all referenced entities by type
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

        $cartItems = [];
        foreach ($allItems as $item) {
            $type = strtoupper((string) $item->getTypeProduit());
            $produitId = (int) ($item->getProduitId() ?? 0);

            $name = 'Item';
            $location = '';
            $icon = '📦';
            $image = null;

            if ($type === 'HEBERGEMENT') {
                $h = $hebergements[$produitId] ?? null;
                $name = $h ? $h->getNom() : 'Hebergement #' . $produitId;
                $location = $h ? ($h->getLocalisation() ?? '') : '';
                $icon = '🏨';
                if ($h) {
                    $image = $h->getImagePath() ?: $h->getImage();
                    if ($image) {
                        // ensure relative path starts with '/'
                        if (strpos($image, '/') !== 0 && strpos($image, 'http') !== 0) {
                            $image = '/'.$image;
                        }
                    }
                }
            } elseif ($type === 'TRANSPORT') {
                $t = $transports[$produitId] ?? null;
                $name = $t ? ($t->getOrigine() . ' → ' . $t->getDestination()) : 'Transport #' . $produitId;
                $location = $t ? ($t->getCompagnie() ?? '') : '';
                $icon = '✈';
                if ($t) {
                    // transports may have an imageUrl or imageTraficUrl
                    $image = $t->getImageUrl() ?: $t->getImageTraficUrl();
                }
            } elseif ($type === 'VOYAGE') {
                $v = $voyages[$produitId] ?? null;
                $name = $v ? ($v->getNom() ?? 'Voyage') : 'Voyage #' . $produitId;
                $location = $v ? ($v->getDestination() ?? '') : '';
                $icon = '🌍';
                if ($v) {
                    $image = $v->getImageUrl();
                    if ($image && strpos($image, '/') !== 0 && strpos($image, 'http') !== 0) {
                        $image = '/'.$image;
                    }
                }
            } elseif ($type === 'ACTIVITE') {
                $a = $activites[$produitId] ?? null;
                $name = $a ? $a->getNom() : 'Activité #' . $produitId;
                $location = $a ? ($a->getVille() . ' · ' . $a->getLieu()) : '';
                $icon = '🎯';
                if ($a) {
                    $image = $a->getImage();
                    if ($image && strpos($image, '/') !== 0 && strpos($image, 'http') !== 0) {
                        $image = '/'.$image;
                    }
                }
            }

            $cartItems[] = [
                'id' => (int) $item->getId(),
                'name' => $name,
                'location' => $location,
                'typeProduit' => $type,
                'icon' => $icon,
                'image' => $image,
                'quantity' => (int) ($item->getQuantite() ?? 1),
                'pricePerPerson' => (float) ($item->getPrixUnitaire() ?? 0),
            ];
        }

        $subtotal = (float) ($panier->getMontantTotalHt() ?? 0);
        $taxes = (float) ($panier->getMontantTva() ?? 0);
        $discount = (float) ($panier->getMontantReduction() ?? 0);
        $total = (float) ($panier->getMontantTtc() ?? 0);

        $availablePromoCodes = array_map(static function (PromoCode $promo): array {
            return [
                'code' => (string) $promo->getCode(),
                'discount' => (int) ($promo->getDiscountPercent() ?? 0),
            ];
        }, $entityManager->getRepository(PromoCode::class)->findBy([
            'userId' => self::CURRENT_USER_ID,
            'isUsed' => false,
        ], ['id' => 'DESC']));

        $selectedPromo = $panier->getCodePromo();
        $discountPercent = 0;
        if ($selectedPromo !== null) {
            $selectedPromoEntity = $entityManager->getRepository(PromoCode::class)->findOneBy([
                'code' => $selectedPromo,
                'userId' => self::CURRENT_USER_ID,
            ]);

            if ($selectedPromoEntity) {
                $discountPercent = (int) ($selectedPromoEntity->getDiscountPercent() ?? 0);
            }
        }

        return [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'taxes' => $taxes,
            'discount' => $discount,
            'promoCode' => $selectedPromo,
            'discountPercent' => $discountPercent,
            'total' => $total,
            'availablePromoCodes' => $availablePromoCodes,
            'selectedPromo' => $selectedPromo,
        ];
    }

    private function refreshPanierTotals(EntityManagerInterface $entityManager, Panier $panier): void
    {
        $allItems = $entityManager->getRepository(ProduitPanier::class)->findBy([
            'panierId' => $panier->getId(),
        ]);

        $subtotal = 0.0;
        foreach ($allItems as $item) {
            $line = (float) ($item->getPrixTotalLigne() ?? 0);
            if ($line <= 0) {
                $line = (float) ($item->getPrixUnitaire() ?? 0) * (int) ($item->getQuantite() ?? 1);
            }
            $subtotal += $line;
        }

        $discount = $this->resolveDiscountAmount($entityManager, $panier, $subtotal);
        $taxes = $subtotal * 0.10;
        $total = max(0, $subtotal + $taxes - $discount);

        $panier->setMontantTotalHt(number_format($subtotal, 2, '.', ''));
        $panier->setMontantTva(number_format($taxes, 2, '.', ''));
        $panier->setMontantTtc(number_format($total, 2, '.', ''));
        $panier->setDateModification(new \DateTime());
    }

    private function multiplyDecimal(string $value, int $qty): string
    {
        $number = (float) str_replace(',', '.', $value);
        return number_format($number * $qty, 2, '.', '');
    }

    private function resolveDiscountAmount(EntityManagerInterface $entityManager, Panier $panier, float $subtotal): float
    {
        $code = $panier->getCodePromo();
        if ($code === null || trim($code) === '') {
            $panier->setMontantReduction('0.00');
            return 0.0;
        }

        $promo = $entityManager->getRepository(PromoCode::class)->findOneBy([
            'code' => $code,
            'userId' => self::CURRENT_USER_ID,
        ]);

        if (!$promo) {
            return (float) ($panier->getMontantReduction() ?? 0);
        }

        $discountPercent = max(0, min(100, (int) ($promo->getDiscountPercent() ?? 0)));
        $discount = $subtotal * ($discountPercent / 100);
        $panier->setMontantReduction(number_format($discount, 2, '.', ''));

        return $discount;
    }
}
