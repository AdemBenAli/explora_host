<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Form\VoyageType;
use App\Repository\VoyageRepository;
use App\Service\GeminiVoyageReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/voyages')]
class VoyageController extends AbstractController
{
    #[Route('', name: 'voyages', methods: ['GET'])]
    #[Route('', name: 'app_voyage_index', methods: ['GET'])]
    public function index(Request $request, VoyageRepository $repository, \App\Service\GeminiService $geminiService): Response
    {
        $selectedDestination = trim((string) $request->query->get('destination', ''));
        $selectedDuration = trim((string) $request->query->get('duration', ''));
        $selectedPrice = trim((string) $request->query->get('price', ''));
        $smartSearch = trim((string) $request->query->get('smart_search', ''));
        $currentPage = max(1, (int) $request->query->get('page', 1));

        $voyages = $repository->findBy([], ['dateDepart' => 'DESC']);

        $destinations = array_values(array_unique(array_map(
            static fn (Voyage $v): string => $v->getDestination(),
            $voyages
        )));
        sort($destinations);

        $durations = array_values(array_unique(array_map(
            static fn (Voyage $v): int => $v->getDureeJours(),
            $voyages
        )));
        sort($durations);

        $priceRanges = ['0-1000', '1000-2000', '2000+'];

        // Apply smart search if requested
        if ($smartSearch !== '') {
            $matchingIds = $geminiService->smartSearch($smartSearch, $voyages);
            $voyages = array_filter($voyages, static fn(Voyage $v) => in_array($v->getId(), $matchingIds, true));
            // Reset filters when doing a smart search
            $selectedDestination = '';
            $selectedDuration = '';
            $selectedPrice = '';
        }

        $voyages = array_values(array_filter(
            $voyages,
            static function (Voyage $v) use ($selectedDestination, $selectedDuration, $selectedPrice): bool {
                if ($selectedDestination !== '' && strcasecmp($v->getDestination(), $selectedDestination) !== 0) {
                    return false;
                }

                if ($selectedDuration !== '' && $v->getDureeJours() !== (int) $selectedDuration) {
                    return false;
                }

                if ($selectedPrice !== '') {
                    $price = $v->getBudgetTotal();
                    if ($selectedPrice === '0-1000' && !($price < 1000)) {
                        return false;
                    }
                    if ($selectedPrice === '1000-2000' && !($price >= 1000 && $price <= 2000)) {
                        return false;
                    }
                    if ($selectedPrice === '2000+' && !($price > 2000)) {
                        return false;
                    }
                }

                return true;
            }
        ));

        $perPage = 6;
        $total = count($voyages);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $perPage;
        $voyages = array_slice($voyages, $offset, $perPage);

        return $this->render('voyage/index.html.twig', [
            'voyages' => $voyages,
            'destinations' => $destinations,
            'durations' => $durations,
            'priceRanges' => $priceRanges,
            'selectedDestination' => $selectedDestination,
            'selectedDuration' => $selectedDuration,
            'selectedPrice' => $selectedPrice,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/popup-create', name: 'app_voyage_popup_create', methods: ['POST'])]
    public function popupCreate(Request $request, EntityManagerInterface $em): Response
    {
        $titre = trim((string) $request->request->get('titre', ''));
        $description = trim((string) $request->request->get('description', ''));
        $destination = trim((string) $request->request->get('destination', ''));
        $dateDebutRaw = (string) $request->request->get('date_debut', '');
        $dateFinRaw = (string) $request->request->get('date_fin', '');
        $budgetRaw = (string) $request->request->get('budget_total', '0');
        $disponibiliteRaw = (string) $request->request->get('disponibilite', '1');

        if ($titre === '' || $destination === '' || $dateDebutRaw === '' || $dateFinRaw === '') {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            return $this->redirectToRoute('app_voyage_index');
        }

        try {
            $dateDebut = new \DateTime($dateDebutRaw);
            $dateFin = new \DateTime($dateFinRaw);
        } catch (\Exception) {
            $this->addFlash('error', 'Format de date invalide.');
            return $this->redirectToRoute('app_voyage_index');
        }

        if ($dateFin < $dateDebut) {
            $this->addFlash('error', 'La date de fin doit etre apres la date de debut.');
            return $this->redirectToRoute('app_voyage_index');
        }

        $budget = (float) $budgetRaw;
        if ($budget <= 0) {
            $this->addFlash('error', 'Le budget doit etre positif.');
            return $this->redirectToRoute('app_voyage_index');
        }

        $disponibilite = max(0, (int) $disponibiliteRaw);
        $duree = (int) $dateDebut->diff($dateFin)->format('%a');

        $voyage = new Voyage();
        $voyage->setTitre($titre)
            ->setDescription($description !== '' ? $description : null)
            ->setDestination($destination)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setDureeJours($duree)
            ->setDisponibilite($disponibilite)
            ->setBudgetTotal($budget);

        $em->persist($voyage);
        $em->flush();

        $this->addFlash('success', 'Voyage ajoute avec succes.');
        return $this->redirectToRoute('app_voyage_index');
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function cartAdd(Request $request, VoyageRepository $repository): Response
    {
        $voyageId = (int) $request->request->get('voyage_id', 0);
        $voyage = $repository->find($voyageId);
        if (!$voyage) {
            $this->addFlash('error', 'Voyage introuvable.');
            return $this->redirectToRoute('app_voyage_index');
        }

        $session = $request->getSession();
        $cart = $session->get('voyage_cart', []);
        $cart[$voyageId] = ($cart[$voyageId] ?? 0) + 1;
        $session->set('voyage_cart', $cart);

        $this->addFlash('success', 'Voyage ajoute au panier.');
        return $this->redirectToRoute('app_voyage_index');
    }

    #[Route('/cart', name: 'voyage_cart_legacy', methods: ['GET'])]
    public function cart(Request $request, VoyageRepository $repository): Response
    {
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/cart/remove/{id}', name: 'voyage_cart_remove_legacy', methods: ['POST'])]
    public function cartRemove(int $id, Request $request): Response
    {
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/ai-analyse', name: 'voyage_ai_analyse', methods: ['GET'])]
    public function aiAnalyse(VoyageRepository $repository, GeminiVoyageReportService $geminiVoyageReportService): JsonResponse
    {
        $reportResult = $geminiVoyageReportService->generateFromSeasonalAnalyses();

        $voyages = $repository->findAll();
        $count = count($voyages);
        $sum = 0.0;
        $destinations = [];
        foreach ($voyages as $voyage) {
            $sum += $voyage->getBudgetTotal();
            $dest = $voyage->getDestination();
            $destinations[$dest] = ($destinations[$dest] ?? 0) + 1;
        }
        arsort($destinations);
        $topDestination = array_key_first($destinations) ?: '-';
        $average = $count > 0 ? $sum / $count : 0.0;

        $summary = $reportResult['source'] === 'gemini'
            ? '✅ Rapport genere avec Gemini.'
            : ($reportResult['status'] === 'empty'
                ? '⚠️ Aucune analyse saisonniere disponible.'
                : '⚠️ Rapport genere en mode local (fallback).');

        return $this->json([
            'summary' => $summary,
            'source' => $reportResult['source'],
            'status' => $reportResult['status'],
            'report' => $reportResult['report'],
            'stats' => [
                'count' => $count,
                'averagePrice' => round($average, 2),
                'topDestination' => $topDestination,
                'seasonalAnalysesCount' => $reportResult['analysesCount'],
            ],
        ]);
    }

    #[Route('/new', name: 'app_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($voyage->getDateDebut() !== null && $voyage->getDateFin() !== null && $voyage->getDateFin() < $voyage->getDateDebut()) {
                $form->get('dateFin')->addError(new \Symfony\Component\Form\FormError('La date de fin doit etre apres la date de debut.'));
            } else {
                $em->persist($voyage);
                $em->flush();

                $this->addFlash('success', 'Voyage ajoute avec succes.');
                return $this->redirectToRoute('app_voyage_index');
            }
        }

        return $this->render('voyage/form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un voyage',
            'submit_label' => 'Ajouter',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Voyage $voyage, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($voyage->getDateDebut() !== null && $voyage->getDateFin() !== null && $voyage->getDateFin() < $voyage->getDateDebut()) {
                $form->get('dateFin')->addError(new \Symfony\Component\Form\FormError('La date de fin doit etre apres la date de debut.'));
            } else {
                $em->flush();

                $this->addFlash('success', 'Voyage modifie avec succes.');
                return $this->redirectToRoute('app_voyage_index');
            }
        }

        return $this->render('voyage/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier le voyage',
            'submit_label' => 'Enregistrer',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_voyage_delete', methods: ['POST'])]
    public function delete(Request $request, Voyage $voyage, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_voyage_' . $voyage->getId(), (string) $request->request->get('_token'))) {
            $em->remove($voyage);
            $em->flush();
            $this->addFlash('success', 'Voyage supprime avec succes.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }

    #[Route('/pdf/export', name: 'app_voyage_pdf', methods: ['GET'])]
    public function generatePdf(VoyageRepository $repository): Response
    {
        // 1. Configure Dompdf
        $pdfOptions = new \Dompdf\Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new \Dompdf\Dompdf($pdfOptions);

        // 2. Fetch data
        $voyages = $repository->findAll();

        // 3. Render the HTML
        $html = $this->renderView('voyage/pdf.html.twig', [
            'voyages' => $voyages
        ]);

        // 4. Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // 5. Render the HTML as PDF
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 6. Output the generated PDF to Browser
        $output = $dompdf->output();
        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="liste_voyages.pdf"'
        ]);
    }
}
