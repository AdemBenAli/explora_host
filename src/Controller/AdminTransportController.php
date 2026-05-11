<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\Transport;
use App\Enum\StatutBillet;
use App\Enum\TypeTransport;
use App\Repository\BilletRepository;
use App\Repository\TransportRepository;
use App\Service\DelayPredictionService;
use App\Service\SaturationPredictionService;
use App\Service\DynamicPricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/transport')]
class AdminTransportController extends AbstractController
{
    /* ══════════════════════════════════════════════════════
       INDEX — Tableau avec tabs, recherche, tri, stats
       Identique à : AdminTransportController.java → chargerDonnees()
                     + switchTab() + rechercherTransports() + appliquerTri()
    ══════════════════════════════════════════════════════ */
    #[Route('', name: 'admin_transport_index', methods: ['GET'])]
    public function index(
        Request $request,
        TransportRepository $transportRepository,
        BilletRepository $billetRepository,
        SaturationPredictionService $saturationService,
        \App\Service\DelayPredictionService $delayPredictionService,
        DynamicPricingService $dynamicPricingService,
        ChartBuilderInterface $chartBuilder
    ): Response {
        // ── Paramètres GET ──
        $typeEnum  = $this->resolveTypeTransport((string) $request->query->get('type', 'AVION'));
        $type      = $typeEnum->name;
        $recherche = trim((string) $request->query->get('recherche', ''));
        $sort      = $request->query->get('sort', 'prix_asc');

        // ── Tous les transports (pour stats globales) ──
        $allTransports = array_values(array_filter(
            $transportRepository->findAll(),
            fn(Transport $tr) => strtoupper((string) $tr->getEtatTrafic()) !== 'SUPPRIME'
        ));
        $allBillets    = $billetRepository->findAll();

        // ── QueryBuilder : filtre par type + recherche ──
        $qb = $transportRepository->createQueryBuilder('t')
            ->where('t.type = :type')
            ->andWhere('t.etatTrafic != :etatSupprime')
            ->setParameter('type', $typeEnum->value)
            ->setParameter('etatSupprime', 'SUPPRIME');

        // Recherche texte libre (identique Java rechercherTransports())
        // → compagnie, origine, destination, numeroVol
        if ($recherche !== '') {
            $q = '%' . mb_strtolower($recherche, 'UTF-8') . '%';
            $qb->andWhere(
                'LOWER(t.compagnie) LIKE :q OR LOWER(t.origine) LIKE :q
                 OR LOWER(t.destination) LIKE :q OR LOWER(t.numeroVol) LIKE :q'
            )->setParameter('q', $q);
        }

        // ── Tri (identique Java appliquerTri() comboTri) ──
        match ($sort) {
            'prix_desc'     => $qb->orderBy('t.prix', 'DESC'),
            'date_desc'     => $qb->orderBy('t.dateDepart', 'DESC'),
            'date_asc'      => $qb->orderBy('t.dateDepart', 'ASC'),
            'heure_asc'     => $qb->orderBy('t.heureDepart', 'ASC'),
            'heure_desc'    => $qb->orderBy('t.heureDepart', 'DESC'),
            'places_desc'   => $qb->orderBy('t.placesDisponibles', 'DESC'),
            'places_asc'    => $qb->orderBy('t.placesDisponibles', 'ASC'),
            default         => $qb->orderBy('t.prix', 'ASC'),    // prix_asc
        };

        $transports = $qb->getQuery()->getResult();

        // 🔥 CALCULER LA SATURATION, LE RETARD ET LE PRIX DYNAMIQUE POUR CHAQUE TRANSPORT (Server-side)
        $dynamicPricingService->loadPopularityMap();
        $saturationData = [];
        $delayData = [];
        foreach ($transports as $t) {
            $dynamicPricingService->updatePriceIfNeeded($t);
            $saturationData[$t->getId()] = $saturationService->predictSaturation($t);
            $delayData[$t->getId()] = $delayPredictionService->predictDelay($t);
        }

        // ── Stats par type (identique Java updateStats()) ──
        $statsParType = [];
        foreach (TypeTransport::cases() as $case) {
            $matchingTransports = array_filter($allTransports, fn($t) => $t->getType() === $case);
            $count = count($matchingTransports);
            
            // On récupère les billets pour ces transports via le repository (unidirectionnel)
            $billetsCount = 0;
            $revenuTnd = 0;
            foreach ($matchingTransports as $t) {
                $tBillets = array_filter($allBillets, fn($b) => $b->getTransport() && $b->getTransport()->getId() === $t->getId());
                $billetsCount += count($tBillets);
                $revenuTnd += array_reduce($tBillets, fn($carry, $b) => $carry + (float)$b->getPrixTotal(), 0.0);
            }
            
            $statsParType[$case->name] = [
                'count' => $count,
                'billets' => $billetsCount,
                'revenu' => $revenuTnd
            ];
        }

        // 🎨 Graphique des revenus (Bundle Consistant)
        $chart = $chartBuilder->createChart(Chart::TYPE_RADAR);
        $chart->setData([
            'labels' => array_keys($statsParType),
            'datasets' => [
                [
                    'label' => 'Revenus cumulés (DT)',
                    'backgroundColor' => 'rgba(247, 148, 29, 0.2)',
                    'borderColor' => '#f7941d',
                    'pointBackgroundColor' => '#f7941d',
                    'data' => array_column($statsParType, 'revenu'),
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [ 'r' => [ 'suggestedMin' => 0 ] ],
            'maintainAspectRatio' => false,
        ]);

        $revenuTotal = array_reduce(
            array_filter($allBillets, fn($b) => $b->getStatut() === StatutBillet::CONFIRME),
            fn($carry, $b) => $carry + (float) $b->getPrixTotal(), 0.0
        );

        // Stats pour le PieChart (identique Java afficherStatistiques())
        $pieData = [];
        $totalBillets = array_sum(array_column($statsParType, 'billets'));
        foreach ($statsParType as $t => $s) {
            if ($s['billets'] > 0) {
                $pieData[] = [
                    'label'   => $t,
                    'value'   => $s['billets'],
                    'percent' => $totalBillets > 0 ? round($s['billets'] * 100 / $totalBillets, 1) : 0,
                ];
            }
        }

        return $this->render('admin_transport/index.html.twig', [
            'transports'     => $transports,
            'currentType'    => $type,
            'recherche'      => $recherche,
            'sort'           => $sort,
            'statsParType'   => $statsParType,
            'revenuTotal'    => $revenuTotal,
            'pieData'        => $pieData,
            'saturationData' => $saturationData,
            'delayData'      => $delayData,
            'revenuChart'    => $chart,
        ]);
    }

    /* ══════════════════════════════════════════════════════
       AJOUTER — Formulaire + validation (identique Java ajouterTransport())
    ══════════════════════════════════════════════════════ */
    #[Route('/ajouter', name: 'admin_transport_ajouter', methods: ['GET','POST'])]
    public function ajouter(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        DynamicPricingService $dynamicPricingService
    ): Response {
        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ajouter_transport', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token invalide.');
                return $this->redirectToRoute('admin_transport_index');
            }

            $data = $this->extractFormData($request);
            $errors = $this->validateTransportData($data);

            if (empty($errors)) {
                try {
                    $transport = $this->buildTransport(new Transport(), $data);
                    $dynamicPricingService->updatePriceIfNeeded($transport);
                    $em->persist($transport);
                    $em->flush();

                    $this->addFlash('success', sprintf(
                        'Transport #%d ajouté avec succès ! %s → %s',
                        $transport->getId(),
                        $transport->getOrigine(),
                        $transport->getDestination()
                    ));
                    return $this->redirectToRoute('admin_transport_index', ['type' => $data['type']]);
                } catch (\Exception $e) {
                    $errors['global'] = 'Erreur lors de l\'ajout : ' . $e->getMessage();
                }
            }
        }

        return $this->render('admin_transport/form.html.twig', [
            'mode'        => 'ajouter',
            'transport'   => null,
            'data'        => $data,
            'errors'      => $errors,
            'currentType' => $request->query->get('type', 'AVION'),
        ]);
    }

    /* ══════════════════════════════════════════════════════
       MODIFIER — Formulaire pré-rempli + validation
       Identique à Java modifierTransport()
    ══════════════════════════════════════════════════════ */
    #[Route('/{id}/modifier', name: 'admin_transport_modifier', methods: ['GET','POST'])]
    public function modifier(
        int $id,
        Request $request,
        TransportRepository $transportRepository,
        EntityManagerInterface $em,
        DynamicPricingService $dynamicPricingService
    ): Response {
        $transport = $transportRepository->find($id);
        if (!$transport) {
            $this->addFlash('error', 'Transport introuvable.');
            return $this->redirectToRoute('admin_transport_index');
        }

        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('modifier_transport_' . $id, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token invalide.');
                return $this->redirectToRoute('admin_transport_index');
            }

            $data   = $this->extractFormData($request);
            $errors = $this->validateTransportData($data);

            if (empty($errors)) {
                try {
                    $this->buildTransport($transport, $data);
                    $dynamicPricingService->loadPopularityMap();
                    $dynamicPricingService->updatePriceIfNeeded($transport);
                    $em->flush();

                    $this->addFlash('success', sprintf(
                        'Transport #%d modifié avec succès !', $transport->getId()
                    ));
                    return $this->redirectToRoute('admin_transport_index', ['type' => $transport->getType()->value]);
                } catch (\Exception $e) {
                    $errors['global'] = 'Erreur lors de la modification : ' . $e->getMessage();
                }
            }
        } else {
            // Pré-remplir le formulaire
            $data = [
                'type'            => $transport->getType()->value,
                'origine'         => $transport->getOrigine(),
                'destination'     => $transport->getDestination(),
                'compagnie'       => $transport->getCompagnie() ?? '',
                'numeroVol'       => $transport->getNumeroVol() ?? '',
                'prix'            => $transport->getPrix(),
                'placesDisponibles'=> $transport->getPlacesDisponibles(),
                'dateDepart'      => $transport->getDateDepart()?->format('Y-m-d') ?? '',
                'dateArrivee'     => $transport->getDateArrivee()?->format('Y-m-d') ?? '',
                'heureDepart'     => $transport->getHeureDepart()?->format('H:i') ?? '',
                'heureArrivee'    => $transport->getHeureArrivee()?->format('H:i') ?? '',
            ];
        }

        return $this->render('admin_transport/form.html.twig', [
            'mode'        => 'modifier',
            'transport'   => $transport,
            'data'        => $data,
            'errors'      => $errors,
            'currentType' => $transport->getType()->value,
        ]);
    }

    /* ══════════════════════════════════════════════════════
       SUPPRIMER — Avec confirmation
       Identique à Java supprimerTransport() + deleteTransport()
    ══════════════════════════════════════════════════════ */
    #[Route('/{id}/supprimer', name: 'admin_transport_supprimer', methods: ['POST'])]
    public function supprimer(
        int $id,
        Request $request,
        TransportRepository $transportRepository,
        BilletRepository $billetRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('supprimer_transport_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_transport_index');
        }

        $transport = $transportRepository->find($id);
        if (!$transport) {
            $this->addFlash('error', 'Transport introuvable.');
            return $this->redirectToRoute('admin_transport_index');
        }

        $type   = $transport->getType()->value;
        $trajet = $transport->getOrigine() . ' → ' . $transport->getDestination();

        try {
            // Annuler tous les billets lies a ce transport
            $billetsLies = $billetRepository->createQueryBuilder('b')
                ->where('b.transport = :transport')
                ->setParameter('transport', $transport)
                ->getQuery()
                ->getResult();

            $nbAnnules = 0;
            foreach ($billetsLies as $billet) {
                if ($billet->getStatut() !== StatutBillet::ANNULE) {
                    $billet->setStatut(StatutBillet::ANNULE);
                    $nbAnnules++;
                }
            }

            // Retirer le transport de la liste sans casser l'historique des billets
            $transport->setEtatTrafic('SUPPRIME');
            $transport->setPlacesDisponibles(0);
            $em->flush();
            $this->addFlash('success', sprintf(
                'Transport "%s" retire de la liste. %d billet(s) passe(s) en Annule.',
                $trajet,
                $nbAnnules
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transport_index', ['type' => $type]);
    }

    /* ══════════════════════════════════════════════════════
       EXPORTER EXCEL — Identique Java exporterVersExcel()
       Génère un CSV téléchargeable (sans dépendance PHPSpreadsheet)
    ══════════════════════════════════════════════════════ */

    #[Route('/exporter', name: 'admin_transport_exporter', methods: ['GET'])]
    public function exporter(
        Request $request,
        TransportRepository $transportRepository
    ): Response {
        $typeEnum = $this->resolveTypeTransport((string) $request->query->get('type', 'AVION'));
        $type     = $typeEnum->name;

        $transports = $transportRepository->createQueryBuilder('t')
            ->where('t.type = :type')
            ->setParameter('type', $typeEnum->value)
            ->orderBy('t.dateDepart', 'ASC')
            ->getQuery()->getResult();

        $rowsHtml = '';
        foreach ($transports as $t) {
            $rowsHtml .= '<tr>'
                . '<td>' . (int) $t->getId() . '</td>'
                . '<td>' . htmlspecialchars($t->getType()->value, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getCompagnie() ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($t->getOrigine(), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($t->getDestination(), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getDateDepart()?->format('d/m/Y') ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getHeureDepart()?->format('H:i') ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getDateArrivee()?->format('d/m/Y') ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getHeureArrivee()?->format('H:i') ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars(number_format((float) $t->getPrix(), 2, '.', ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int) $t->getPlacesDisponibles() . '</td>'
                . '<td>' . htmlspecialchars((string) ($t->getNumeroVol() ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }

        $html = '<html><head><meta charset="UTF-8"></head><body>'
            . '<table border="1">'
            . '<tr>'
            . '<th>ID</th><th>Type</th><th>Compagnie</th><th>Origine</th><th>Destination</th>'
            . '<th>Date Départ</th><th>Heure Départ</th><th>Date Arrivée</th><th>Heure Arrivée</th>'
            . '<th>Prix (DT)</th><th>Places Disponibles</th><th>N° Vol/Ticket</th>'
            . '</tr>'
            . $rowsHtml
            . '</table></body></html>';

        $response = new Response("\xEF\xBB\xBF" . $html);
        $filename = 'transports_' . strtolower($type) . '_' . date('Y-m-d') . '.xls';
        $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');

        return $response;
    }

    /** Extrait et nettoie les données du formulaire POST */
    private function extractFormData(Request $r): array
    {
        return [
            'type'             => strtoupper(trim($r->request->get('type', 'AVION'))),
            'origine'          => trim($r->request->get('origine', '')),
            'destination'      => trim($r->request->get('destination', '')),
            'compagnie'        => trim($r->request->get('compagnie', '')),
            'numeroVol'        => trim($r->request->get('numeroVol', '')),
            'prix'             => $r->request->get('prix', ''),
            'placesDisponibles'=> $r->request->get('placesDisponibles', ''),
            'dateDepart'       => trim($r->request->get('dateDepart', '')),
            'dateArrivee'      => trim($r->request->get('dateArrivee', '')),
            'heureDepart'      => trim($r->request->get('heureDepart', '')),
            'heureArrivee'     => trim($r->request->get('heureArrivee', '')),
        ];
    }

    /** Validation complète — contrôle de saisie (identique Java validations) */
    private function validateTransportData(array $data): array
    {
        $errors = [];
        $today = new \DateTimeImmutable('today');
        $namePattern = '/^[\p{L}\s\-\'.]+$/u';

        // Type
        $validTypes = ['AVION','BATEAU','TRAIN','VOITURE','BUS','TAXI'];
        if (!in_array($data['type'], $validTypes, true)) {
            $errors['type'] = 'Type de transport invalide.';
        }

        // Origine
        if (empty($data['origine'])) {
            $errors['origine'] = 'L\'origine est obligatoire.';
        } elseif (strlen($data['origine']) < 2) {
            $errors['origine'] = 'L\'origine doit contenir au moins 2 caractères.';
        } elseif (strlen($data['origine']) > 100) {
            $errors['origine'] = 'L\'origine ne doit pas dépasser 100 caractères.';
        } elseif (!preg_match($namePattern, $data['origine'])) {
            $errors['origine'] = 'L\'origine ne doit pas contenir de chiffres.';
        }

        // Destination
        if (empty($data['destination'])) {
            $errors['destination'] = 'La destination est obligatoire.';
        } elseif (strlen($data['destination']) < 2) {
            $errors['destination'] = 'La destination doit contenir au moins 2 caractères.';
        } elseif (strlen($data['destination']) > 100) {
            $errors['destination'] = 'La destination ne doit pas dépasser 100 caractères.';
        } elseif (!preg_match($namePattern, $data['destination'])) {
            $errors['destination'] = 'La destination ne doit pas contenir de chiffres.';
        }

        // Origine ≠ Destination
        if (!empty($data['origine']) && !empty($data['destination']) &&
            strtolower($data['origine']) === strtolower($data['destination'])) {
            $errors['destination'] = 'La destination doit être différente de l\'origine.';
        }

        // Prix
        if ($data['prix'] === '' || $data['prix'] === null) {
            $errors['prix'] = 'Le prix est obligatoire.';
        } elseif (!preg_match('/^\d+(?:[.,]\d{1,2})?$/', (string) $data['prix'])) {
            $errors['prix'] = 'Le prix doit contenir uniquement des chiffres.';
        } elseif ((float) str_replace(',', '.', (string) $data['prix']) <= 0) {
            $errors['prix'] = 'Le prix doit être un nombre positif.';
        } elseif ((float) str_replace(',', '.', (string) $data['prix']) > 99999.99) {
            $errors['prix'] = 'Le prix ne peut pas dépasser 99 999,99 DT.';
        }

        // Places disponibles
        if ($data['placesDisponibles'] === '' || $data['placesDisponibles'] === null) {
            $errors['placesDisponibles'] = 'Le nombre de places est obligatoire.';
        } elseif (!preg_match('/^\d+$/', (string) $data['placesDisponibles'])) {
            $errors['placesDisponibles'] = 'Le nombre de places doit être un entier positif ou nul.';
        } elseif ((int)$data['placesDisponibles'] > 9999) {
            $errors['placesDisponibles'] = 'Le nombre de places ne peut pas dépasser 9 999.';
        }

        // Taxi / Voiture: max 4 places
        if (
            isset($data['type'], $data['placesDisponibles'])
            && in_array($data['type'], ['TAXI', 'VOITURE'], true)
            && preg_match('/^\d+$/', (string) $data['placesDisponibles'])
            && (int) $data['placesDisponibles'] > 4
        ) {
            $errors['placesDisponibles'] = 'Maximum 4 places pour Taxi/Voiture.';
        }

        // Date de départ
        if (empty($data['dateDepart'])) {
            $errors['dateDepart'] = 'La date de départ est obligatoire.';
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $data['dateDepart']);
            if (!$d) {
                $errors['dateDepart'] = 'Format de date invalide.';
            } else {
                $departDate = \DateTimeImmutable::createFromMutable($d);
                // On compare avec "hier" pour éviter les bugs de timezone si on veut mettre la date de "aujourd'hui"
                if ($departDate < $today->modify('-1 day')) {
                    $errors['dateDepart'] = 'La date ne peut pas être dans le passé.';
                }
            }
        }

        // Heure de départ
        if (empty($data['heureDepart'])) {
            $errors['heureDepart'] = 'L\'heure de départ est obligatoire.';
        } else {
            $h = \DateTime::createFromFormat('H:i', $data['heureDepart']);
            if (!$h) {
                $errors['heureDepart'] = 'Format d\'heure invalide (HH:MM).';
            }
        }

        // Date d'arrivée (optionnelle mais doit être >= date départ si fournie)
        if (!empty($data['dateArrivee'])) {
            $dArr = \DateTime::createFromFormat('Y-m-d', $data['dateArrivee']);
            if (!$dArr) {
                $errors['dateArrivee'] = 'Format de date d\'arrivée invalide.';
            } elseif (!empty($data['dateDepart'])) {
                $dDep = \DateTime::createFromFormat('Y-m-d', $data['dateDepart']);
                if ($dArr && $dDep && $dArr < $dDep) {
                    $errors['dateArrivee'] = 'La date d\'arrivée doit être >= à la date de départ.';
                }
            }
        }

        // Heure d'arrivée (optionnelle)
        if (!empty($data['heureArrivee'])) {
            $hArr = \DateTime::createFromFormat('H:i', $data['heureArrivee']);
            if (!$hArr) {
                $errors['heureArrivee'] = 'Format d\'heure d\'arrivée invalide (HH:MM).';
            }
        }

        // Compagnie (optionnelle mais max 100)
        if (!empty($data['compagnie']) && strlen($data['compagnie']) > 100) {
            $errors['compagnie'] = 'Le nom de la compagnie ne doit pas dépasser 100 caractères.';
        } elseif (!empty($data['compagnie']) && !preg_match($namePattern, $data['compagnie'])) {
            $errors['compagnie'] = 'La compagnie ne doit pas contenir de chiffres.';
        }

        // Numéro vol (optionnel mais max 50)
        if (!empty($data['numeroVol']) && strlen($data['numeroVol']) > 50) {
            $errors['numeroVol'] = 'Le numéro de vol ne doit pas dépasser 50 caractères.';
        }

        return $errors;
    }

    /** Applique les données validées à l'entité Transport */
    private function buildTransport(Transport $transport, array $data): Transport
    {
        $transport->setType($this->resolveTypeTransport((string) ($data['type'] ?? '')));
        $transport->setOrigine($data['origine']);
        $transport->setDestination($data['destination']);
        $transport->setCompagnie($data['compagnie'] ?: null);
        $transport->setNumeroVol($data['numeroVol'] ?: null);
        $transport->setPrix((string)(float) str_replace(',', '.', (string) $data['prix']));
        $transport->setPlacesDisponibles((int)$data['placesDisponibles']);

        $transport->setDateDepart(
            \DateTime::createFromFormat('Y-m-d', $data['dateDepart']) ?: null
        );
        $transport->setHeureDepart(
            \DateTime::createFromFormat('H:i', $data['heureDepart']) ?: null
        );

        if (!empty($data['dateArrivee'])) {
            $transport->setDateArrivee(
                \DateTime::createFromFormat('Y-m-d', $data['dateArrivee']) ?: null
            );
        } else {
            $transport->setDateArrivee(null);
        }

        if (!empty($data['heureArrivee'])) {
            $transport->setHeureArrivee(
                \DateTime::createFromFormat('H:i', $data['heureArrivee']) ?: null
            );
        } else {
            $transport->setHeureArrivee(null);
        }

        if ($transport->getId() === null) {
            $transport->setCreatedAt(new \DateTime());
        }

        return $transport;
    }

    /** Accepte TAXI/taxi et retourne l'enum TypeTransport correspondant */
    private function resolveTypeTransport(string $rawType): TypeTransport
    {
        $normalized = strtoupper(trim($rawType));

        foreach (TypeTransport::cases() as $case) {
            if ($case->name === $normalized || strtoupper($case->value) === $normalized) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('Type de transport invalide: "%s".', $rawType));
    }

    /* ══════════════════════════════════════════════════════
       ANALYSER — Analyse IA Trafic via Gemini
       ══════════════════════════════════════════════════════ */
    #[Route('/{id}/analyser-ia', name: 'admin_transport_analyser_ia', methods: ['POST'])]
    public function analyserTrafic(
        Transport $transport,
        Request $request,
        EntityManagerInterface $em,
        \App\Service\TrafficAnalysisService $trafficService,
        \App\Service\DelayPredictionService $delayPredictionService,
        DynamicPricingService $dynamicPricingService
    ): Response {
        $file = $request->files->get('image');

        if (!$file) {
            return $this->json(['error' => 'Aucune image recue.'], 400);
        }

        try {
            // Analyse via Gemini
            $result = $trafficService->analyserImage($file->getPathname());

            // Sauvegarder dans la DB
            $transport->setEtatTrafic($result['etat']);
            $transport->setScoreConfiance((string) $result['scoreConfiance']);
            $transport->setDerniereAnalyse(new \DateTime());
            
            $dynamicPricingService->loadPopularityMap();
            $dynamicPricingService->updatePriceIfNeeded($transport);

            $em->flush();

            // 🔥 Calculer le NOUVEAU retard apr\u00e8s analyse
            $newDelay = $delayPredictionService->predictDelay($transport);

            return $this->json([
                'success' => true,
                'etat' => $result['etat'],
                'label' => $result['label'],
                'icone' => $result['icone'],
                'score' => $result['scoreConfiance'],
                'justification' => $result['justification'],
                'delay' => $newDelay // On renvoie les nouvelles infos de retard
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
