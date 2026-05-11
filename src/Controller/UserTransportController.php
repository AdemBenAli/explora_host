<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\Panier;
use App\Entity\ProduitPanier;
use App\Entity\Transport;
use App\Enum\StatutBillet;
use App\Enum\TypeTransport;
use App\Service\AviationStackApiService;
use App\Service\MapApiService;
use App\Service\WeatherApiService;
use App\Service\CarbonFootprintService;
use App\Repository\BilletRepository;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserTransportController extends AbstractController
{
    /* ══════════════════════════════════════════════════════
       INDEX — Liste des transports avec recherche + tri
       Identique à : chargerTransportsParType() + rechercherTransports()
       ══════════════════════════════════════════════════════ */
    #[Route('/user/transport', name: 'user_transport')]
    public function index(
        Request $request, 
        TransportRepository $transportRepository, 
        CarbonFootprintService $carbonFootprintService,
        \App\Service\EcoScoreService $ecoScoreService,
        \App\Service\DelayPredictionService $delayPredictionService,
        \App\Service\DynamicPricingService $dynamicPricingService,
        \App\Service\SaturationPredictionService $saturationService,
        EntityManagerInterface $em
    ): Response
    {
        // userId fixe (mocké)
        $userId = 5;

        // ── Paramètres GET ──
        $type             = strtoupper((string) $request->query->get('type', 'AVION'));
        $searchOrigine    = trim((string) $request->query->get('origine', ''));
        $searchDestination= trim((string) $request->query->get('destination', ''));
        $searchDateDepart = trim((string) $request->query->get('dateDepart', ''));
        $searchDateRetour = trim((string) $request->query->get('dateRetour', ''));
        $searchPassagers  = max(1, (int) $request->query->get('passagers', 1));
        $sort             = (string) $request->query->get('sort', 'prix_asc');

        // ── QueryBuilder ──
        $qb = $transportRepository->createQueryBuilder('t');

        // Filtre par type de transport (tab actif)
        if ($type !== '') {
            $qb->andWhere('t.type = :type')
               ->setParameter('type', $type);
        }

        // Filtre par origine (LIKE insensible à la casse)
        if ($searchOrigine !== '') {
            $qb->andWhere('LOWER(t.origine) LIKE :origine')
               ->setParameter('origine', '%' . mb_strtolower($searchOrigine, 'UTF-8') . '%');
        }

        // Filtre par destination (LIKE insensible à la casse)
        if ($searchDestination !== '') {
            $qb->andWhere('LOWER(t.destination) LIKE :destination')
               ->setParameter('destination', '%' . mb_strtolower($searchDestination, 'UTF-8') . '%');
        }

        // Filtre par date de départ
        if ($searchDateDepart !== '') {
            $dateDepart = \DateTime::createFromFormat('Y-m-d', $searchDateDepart);
            if ($dateDepart !== false) {
                $qb->andWhere('t.dateDepart = :dateDepart')
                   ->setParameter('dateDepart', $dateDepart);
            }
        }

        // Filtre par date de retour
        if ($searchDateRetour !== '') {
            $dateRetour = \DateTime::createFromFormat('Y-m-d', $searchDateRetour);
            if ($dateRetour !== false) {
                $qb->andWhere('t.dateArrivee = :dateRetour')
                   ->setParameter('dateRetour', $dateRetour);
            }
        }

        // Filtre places disponibles >= passagers (identique Java)
        $qb->andWhere('t.placesDisponibles >= :passagers')
           ->setParameter('passagers', $searchPassagers);

        // ── Tri (identique Java trierTransports()) ──
        match ($sort) {
            'prix_desc'  => $qb->orderBy('t.prix', 'DESC'),
            'date_asc'   => $qb->orderBy('t.dateDepart', 'ASC'),
            'date_desc'  => $qb->orderBy('t.dateDepart', 'DESC'),
            default      => $qb->orderBy('t.prix', 'ASC'),   // prix_asc
        };

        $transports = $qb->getQuery()->getResult();

        // ── Calcul Co2 & Tarification Dynamique ──
        $dynamicPricingService->loadPopularityMap();
        $needsFlush = false;
        foreach ($transports as $transport) {
            // 1. Empreinte Carbonne
            if ($transport->getEmissionsKgCo2() === null || (float) $transport->getEmissionsKgCo2() == 0 || $transport->getDistanceKm() === null) {
                $empreinte = $carbonFootprintService->calculerEmpreinte($transport);
                $transport->setDistanceKm($empreinte['distanceKm']);
                $transport->setEmissionsKgCo2($empreinte['emissionsKgCO2']);
                $transport->setCategorieEcologique($empreinte['categorie']);
                $em->persist($transport);
                $needsFlush = true;
            }

            // 2. Tarification Dynamique (PRO)
            $dynamicPricingService->updatePriceIfNeeded($transport);
            $needsFlush = true; 
        }
        
        if ($needsFlush) {
            $em->flush();
        }

        // 🔥 CALCULER LA SATURATION POUR CHAQUE TRANSPORT (Server-side)
        // Note: On utilise déjà la collection $transports chargée plus haut, pas besoin de ré-interroger la DB
        $saturationData = [];
        foreach ($transports as $t) {
            $saturationData[$t->getId()] = $saturationService->predictSaturation($t);
        }


        // ── Obtenir Infos Eco Score ──
        $ecoScore = $ecoScoreService->getScore($userId);

        // ── Placeholders selon le type ──
        [$originePlaceholder, $destinationPlaceholder] = match ($type) {
            'TRAIN'   => ['Gare de départ', 'Gare d\'arrivée'],
            'BATEAU'  => ['Port de départ', 'Port d\'arrivée'],
            'BUS'     => ['Station de départ', 'Station d\'arrivée'],
            'VOITURE',
            'TAXI'    => ['Lieu de prise en charge', 'Lieu de destination'],
            default   => ['Aéroport de départ', 'Aéroport d\'arrivée'],
        };

        // ── Détection Trajet Court (Suggestions Vélo) ──
        $shortTrip = null;
        if ($searchOrigine && $searchDestination) {
            $dist = $carbonFootprintService->getDistance($searchOrigine, $searchDestination);
            if ($dist > 0 && $dist < 10) {
                $shortTrip = [
                    'distance' => round($dist, 1),
                    'type'     => $type ?: 'TRANSPORT'
                ];
            }
        }

        return $this->render('transport/user_index.html.twig', [
            'transports'           => $transports,
            'currentType'          => $type,
            'searchOrigine'        => $searchOrigine,
            'searchDestination'    => $searchDestination,
            'searchDateDepart'     => $searchDateDepart,
            'searchDateRetour'     => $searchDateRetour,
            'searchPassagers'      => $searchPassagers,
            'sort'                 => $sort,
            'originePlaceholder'   => $originePlaceholder,
            'destinationPlaceholder' => $destinationPlaceholder,
            'ecoScore'             => $ecoScore,
            'saturationData'       => $saturationData,
            'shortTrip'            => $shortTrip
        ]);
    }


    #[Route('/user/transport/weather', name: 'user_transport_weather', methods: ['GET'])]
    public function weather(Request $request, WeatherApiService $weatherApiService): JsonResponse
    {
        $city = trim((string) $request->query->get('city', 'Tunis'));
        if ($city === '') {
            return $this->json(['error' => 'Ville manquante.'], 400);
        }

        $data = $weatherApiService->getWeatherByCity($city);
        if ($data === null) {
            return $this->json(['error' => 'Impossible de recuperer la meteo.'], 502);
        }

        return $this->json($data);
    }

    #[Route('/user/transport/route-map', name: 'user_transport_route_map', methods: ['GET'])]
    public function routeMap(Request $request, \App\Service\EcoScoreService $ecoScoreService): Response
    {
        $userId = 5; // Mocked userId
        return $this->render('transport/route_map.html.twig', [
            'origin' => trim((string) $request->query->get('origin', 'Tunis')),
            'destination' => trim((string) $request->query->get('destination', 'Sousse')),
            'ecoScore' => $ecoScoreService->getScore($userId),
        ]);
    }

    #[Route('/user/transport/route-data', name: 'user_transport_route_data', methods: ['GET'])]
    public function routeData(Request $request, MapApiService $mapApiService): JsonResponse
    {
        $origin = trim((string) $request->query->get('origin', ''));
        $destination = trim((string) $request->query->get('destination', ''));

        if ($origin === '') {
            return $this->json(['error' => 'Origine et destination obligatoires.'], 400);
        }

        $route = $mapApiService->buildRouteByCity($origin, $destination);
        if ($route === null) {
            return $this->json(['error' => 'Villes invalides ou itineraire introuvable.'], 422);
        }

        return $this->json($route);
    }

    #[Route('/user/transport/check-distance', name: 'user_transport_check_distance', methods: ['GET'])]
    public function checkDistance(Request $request, MapApiService $mapApiService, \App\Service\EcoService $ecoService): JsonResponse
    {
        $origin = trim((string) $request->query->get('origin', ''));
        $destination = trim((string) $request->query->get('destination', ''));

        if ($origin === '' || $destination === '') {
            return $this->json(['error' => 'Origine et destination obligatoires.'], 400);
        }

        // Tenter l'itinéraire précis via OpenRoute/OSRM
        $distanceKm = -1.0;
        $routeData = $mapApiService->buildRouteByCity($origin, $destination);
        
        if ($routeData !== null && isset($routeData['route']['features'][0]['properties']['segments'][0]['distance'])) {
            $distanceKm = $routeData['route']['features'][0]['properties']['segments'][0]['distance'] / 1000;
        } else {
            // Fallback : Utilisation de la formule Haversine (distance à vol d'oiseau)
            $distanceKm = $ecoService->calculerDistance($origin, $destination);
        }

        if ($distanceKm < 0) {
            return $this->json(['error' => 'Villes introuvables ou trop éloignées.'], 404);
        }

        return $this->json([
            'distanceKm' => $distanceKm,
            'isShort' => ($distanceKm > 0 && $distanceKm <= 10.0),
            'origin' => $origin,
            'destination' => $destination
        ]);
    }

    #[Route('/user/transport/flight-map', name: 'user_transport_flight_map', methods: ['GET'])]
    public function flightMap(): Response
    {
        return $this->render('transport/flight_map.html.twig');
    }

    #[Route('/user/transport/flight-data', name: 'user_transport_flight_data', methods: ['GET'])]
    public function flightData(Request $request, AviationStackApiService $aviationStackApiService): JsonResponse
    {
        $dep = strtoupper(trim((string) $request->query->get('dep', 'TUN')));
        $arr = strtoupper(trim((string) $request->query->get('arr', '')));
        $arr = $arr === '' ? null : $arr;

        if ($dep === '') {
            return $this->json(['error' => 'Code IATA depart manquant.'], 400);
        }

        $flights = $aviationStackApiService->searchFlights($dep, $arr);
        return $this->json([
            'dep' => $dep,
            'arr' => $arr,
            'count' => count($flights),
            'flights' => $flights,
        ]);
    }

    #[Route('/user/transport/real-flights', name: 'user_transport_real_flights', methods: ['GET'])]
    public function realFlights(Request $request, AviationStackApiService $aviationStackApiService): JsonResponse
    {
        $depRaw = trim((string) $request->query->get('dep', 'TUN'));
        $arrRaw = trim((string) $request->query->get('arr', ''));

        // Mapper intelligent : Noms de villes -> Codes IATA
        $iataMap = [
            'TUNIS' => 'TUN', 'PARIS' => 'CDG', 'LYON' => 'LYS', 'MARSEILLE' => 'MRS',
            'LONDRES' => 'LHR', 'LONDON' => 'LHR', 'NICE' => 'NCE', 'ROME' => 'FCO',
            'ISTANBUL' => 'IST', 'DUBAI' => 'DXB', 'SOUSSE' => 'MIR', 'MONASTIR' => 'MIR',
            'DJERBA' => 'DJE', 'SFAX' => 'SFA'
        ];

        $dep = strtoupper($depRaw);
        $dep = $iataMap[$dep] ?? (strlen($dep) === 3 ? $dep : 'TUN');

        $arr = strtoupper($arrRaw);
        $arr = $iataMap[$arr] ?? (strlen($arr) === 3 ? $arr : null);

        if ($dep === '') {
            return $this->json(['error' => 'Code IATA depart manquant.'], 400);
        }

        $items = [];
        try {
            $flights = $aviationStackApiService->searchFlights($dep, $arr);
        } catch (\Throwable $e) {
            $flights = [];
        }

        // Si l'API ne renvoie rien, on génère des vols de démonstration
        if (count($flights) === 0) {
            $demoAirlines = ['Tunisair', 'Air France', 'Transavia', 'Emirates', 'Lufthansa', 'Qatar Airways'];
            $destStr = $arrRaw ?: ($arr ?: 'PARIS');
            for ($i = 0; $i < 6; $i++) {
                $items[] = [
                    'id' => 200000 + $i,
                    'type' => TypeTransport::AVION->value,
                    'origine' => $depRaw ?: 'Tunis',
                    'destination' => $destStr,
                    'dateDepart' => date('d/m/Y'),
                    'dateArrivee' => date('d/m/Y'),
                    'heureDepart' => (10 + $i) . ':25',
                    'heureArrivee' => (12 + $i) . ':50',
                    'compagnie' => $demoAirlines[$i % count($demoAirlines)],
                    'numeroVol' => 'EXP' . random_int(100, 999),
                    'prix' => number_format((float) random_int(450, 950), 2, '.', ''),
                    'placesDisponibles' => random_int(15, 90),
                    'isRealFlight' => true,
                ];
            }
        } else {
            foreach ($flights as $idx => $flight) {
                $depTime = (string) ($flight['departure_time'] ?? '');
                $date = date('d/m/Y');
                $heure = '10:00';
                if ($depTime !== '') {
                    try {
                        $dt = new \DateTimeImmutable($depTime);
                        $date = $dt->format('d/m/Y');
                        $heure = $dt->format('H:i');
                    } catch (\Throwable) {}
                }
                $items[] = [
                    'id' => 100000 + $idx,
                    'type' => TypeTransport::AVION->value,
                    'origine' => (string) ($flight['departure'] ?? 'Depart'),
                    'destination' => (string) ($flight['arrival'] ?? 'Arrivee'),
                    'dateDepart' => $date,
                    'dateArrivee' => $date,
                    'heureDepart' => $heure,
                    'heureArrivee' => '--:--',
                    'compagnie' => (string) ($flight['airline'] ?? 'Compagnie inconnue'),
                    'numeroVol' => (string) ($flight['flight_number'] ?? 'N/A'),
                    'prix' => number_format((float) random_int(350, 650), 2, '.', ''),
                    'placesDisponibles' => random_int(20, 100),
                    'isRealFlight' => true,
                ];
            }
        }

        return $this->json([
            'dep' => $dep,
            'arr' => $arr,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /* ══════════════════════════════════════════════════════
       DETAIL — Page détail d'un transport
       ══════════════════════════════════════════════════════ */
    #[Route('/user/transport/{id}', name: 'user_transport_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(Transport $transport, CarbonFootprintService $carbonFootprintService): Response
    {
        if ($transport->getEmissionsKgCo2() === null || (float) $transport->getEmissionsKgCo2() == 0) {
            $empreinte = $carbonFootprintService->calculerEmpreinte($transport);
            $transport->setDistanceKm($empreinte['distanceKm']);
            $transport->setEmissionsKgCo2($empreinte['emissionsKgCO2']);
            $transport->setCategorieEcologique($empreinte['categorie']);
        }

        return $this->render('user_transport/detail.html.twig', [
            'transport' => $transport,
        ]);
    }

    /* ══════════════════════════════════════════════════════
       RÉSERVER — Traitement de la réservation (POST)
       Identique à Java : billetService.creerBillet()
       ══════════════════════════════════════════════════════ */
    #[Route('/user/billet/reserver', name: 'user_billet_reserver', methods: ['POST'])]
    public function reserverTransport(
        Request $request,
        EntityManagerInterface $em,
        TransportRepository $transportRepository,
        \App\Service\EcoScoreService $ecoScoreService,
        \App\Service\DynamicPricingService $dynamicPricingService
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reserver', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('user_transport', ['type' => 'AVION']);
        }

        $transportId  = (int) $request->request->get('transportId');
        $nombrePlaces = (int) $request->request->get('nombrePlaces', 1);

        // Récupérer le transport
        $transport = $transportRepository->find($transportId);
        if (!$transport) {
            $this->addFlash('error', 'Transport introuvable.');
            return $this->redirectToRoute('user_transport');
        }

        $isUnitTransport = in_array($transport->getType(), [TypeTransport::TAXI, TypeTransport::VOITURE], true);

        // Valider le nombre de places
        if ($nombrePlaces < 1) {
            $this->addFlash('error', 'Le nombre de places doit être supérieur à 0.');
            return $this->redirectToRoute('user_transport', ['type' => $transport->getType()->value]);
        }

        if (!$isUnitTransport && $nombrePlaces > $transport->getPlacesDisponibles()) {
            $this->addFlash('error', sprintf(
                'Pas assez de places disponibles. Maximum : %d place(s).',
                $transport->getPlacesDisponibles()
            ));
            return $this->redirectToRoute('user_transport', ['type' => $transport->getType()->value]);
        }

        // userId — à remplacer par $this->getUser()->getId() si vous avez l'auth
        $userId = 5;

        try {
            // Créer le billet (identique Java billetService.creerBillet())
            $facturedUnits = $isUnitTransport ? 1 : $nombrePlaces;
            $seatsToReserve = $isUnitTransport ? $transport->getPlacesDisponibles() : $nombrePlaces;
            $billet = new Billet($transport, $userId, $facturedUnits);
            $billet->setStatut(StatutBillet::EN_ATTENTE);
            $billet->setCreatedAt(new \DateTime());

            // Décrémenter les places disponibles
            $transport->reserverPlaces($seatsToReserve);

            // 💰 METTRE À JOUR LE PRIX TOUT DE SUITE APRÈS L'ACHAT (Identique Java dynamicPricingService.updatePrice)
            $dynamicPricingService->loadPopularityMap();
            $dynamicPricingService->updatePriceIfNeeded($transport);

            // 🛡️ SÉCURITÉ : Forcer la persistance du transport pour être sûr que les places sont décomptées
            $em->persist($transport);
            $em->persist($billet);
            $em->flush();

            // 🎯 AJOUTER POINTS ÉCOLOGIQUES (Identique Java EcoScoreService)
            $scoreInfo = $ecoScoreService->ajouterPoints($userId, $transport, false);

            // ── Add transport to cart ──
            $panier = $em->getRepository(Panier::class)->findOneBy([
                'userId' => 1, 'statut' => 'ACTIF',
            ], ['id' => 'DESC']);

            if (!$panier) {
                $panier = new Panier();
                $panier->setUserId(1);
                $panier->setStatut('ACTIF');
                $panier->setDateCreation(new \DateTime());
                $panier->setDateModification(new \DateTime());
                $panier->setMontantTotalHt('0');
                $panier->setMontantTva('0');
                $panier->setMontantTtc('0');
                $panier->setMontantReduction('0');
                $em->persist($panier);
                $em->flush();
            }

            $cartItem = new ProduitPanier();
            $cartItem->setPanierId((int) $panier->getId());
            $cartItem->setProduitId((int) $transport->getId());
            $cartItem->setTypeProduit('TRANSPORT');
            $cartItem->setDateAjout(new \DateTime());
            $cartItem->setQuantite($facturedUnits);
            $cartItem->setPrixUnitaire($transport->getPrix());
            $cartItem->setPrixTotalLigne(number_format((float) $billet->getPrixTotal(), 2, '.', ''));
            $em->persist($cartItem);

            // Refresh panier totals
            $allPanierItems = $em->getRepository(ProduitPanier::class)->findBy(['panierId' => $panier->getId()]);
            $subtotal = 0.0;
            foreach ($allPanierItems as $pi) {
                $line = (float) ($pi->getPrixTotalLigne() ?? 0);
                if ($line <= 0) {
                    $line = (float) ($pi->getPrixUnitaire() ?? 0) * (int) ($pi->getQuantite() ?? 1);
                }
                $subtotal += $line;
            }
            $subtotal += (float) $billet->getPrixTotal();
            $taxes = $subtotal * 0.10;
            $total = $subtotal + $taxes;
            $panier->setMontantTotalHt(number_format($subtotal, 2, '.', ''));
            $panier->setMontantTva(number_format($taxes, 2, '.', ''));
            $panier->setMontantTtc(number_format($total, 2, '.', ''));
            $panier->setDateModification(new \DateTime());
            $em->flush();

            $this->addFlash('success', sprintf(
                'Réservation réussie ! Billet #%d — %s → %s — %.2f DT — Added to cart! (Points %s: %d/%d)',
                $billet->getId(),
                $transport->getOrigine(),
                $transport->getDestination(),
                $billet->getPrixTotal(),
                $scoreInfo['badgeNiveau'] ?? '',
                $scoreInfo['pointsActuels'] ?? 0,
                $scoreInfo['pointsTotal'] ?? 0
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réservation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_panier_index');
    }

    /* ══════════════════════════════════════════════════════
       ÉCO-FONCTIONNALITÉS (VÉLO & SCORE)
       ══════════════════════════════════════════════════════ */
    #[Route('/user/eco/velo-shop', name: 'user_eco_velo_shop', methods: ['GET'])]
    public function getVeloShop(Request $request, \App\Service\EcoService $ecoService): JsonResponse
    {
        $ville = trim((string) $request->query->get('ville', ''));
        if ($ville === '') {
            return $this->json(['error' => 'Ville manquante'], 400);
        }

        $shop = $ecoService->trouverBoutiqueDansVille($ville);
        if (!$shop) {
            return $this->json(['error' => 'Aucune boutique trouvée proche'], 404);
        }

        return $this->json($shop);
    }

    #[Route('/user/eco/generer-code', name: 'user_eco_generer_code', methods: ['POST'])]
    public function genererCodeVelo(Request $request, \App\Service\EcoService $ecoService, \App\Service\EcoScoreService $ecoScoreService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $origine = trim((string) ($data['origine'] ?? ''));
            $destination = trim((string) ($data['destination'] ?? ''));
            
            // userId fixe
            $userId = 5;

            if ($origine === '' || $destination === '') {
                return $this->json(['error' => 'Origine ou destination manquante'], 400);
            }

            $code = $ecoService->genererCodePromo($userId, $origine, $destination);
            if (!$code) {
                return $this->json(['error' => 'Impossible de generer le code'], 500);
            }
            
            // Calculer la distance et le CO2 évité pour l'impact écologique
            $distance = $ecoService->calculerDistance($origine, $destination);
            if ($distance <= 0) $distance = 5.0; // Fallback si distance inconnue
            
            $co2Evite = $ecoService->calculerCO2Evite($distance, 'VOITURE'); 

            // Créer un transport factice pour ajouter les points
            $dummyTransport = new \App\Entity\Transport();
            $dummyTransport->setOrigine($origine);
            $dummyTransport->setDestination($destination);
            $dummyTransport->setEmissionsKgCO2($co2Evite); 
            $dummyTransport->setDistanceKm($distance);
            
            $ecoScoreService->ajouterPoints($userId, $dummyTransport, true);
            
            return $this->json([
                'code' => $code,
                'reduction' => '10%',
                'pointsVelo' => $ecoScoreService->getPointsVelo()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
}
