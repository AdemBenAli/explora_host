<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\AxeActivite;
use App\Entity\AxeHebergement;
use App\Entity\AxeTransport;
use App\Entity\AxeVoyage;
use App\Entity\Coupon;
use App\Entity\Preferences;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/traveler-dashboard', name: 'app_traveler_dashboard')]
    public function travelerDashboard(Request $request): Response
    {
        $role = strtolower($request->getSession()->get('user_role', ''));
        if ($role === 'voyageur') {
            return $this->redirectToRoute('voyageur_index');
        }
        return $this->render('dashboard/traveler.html.twig');
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('app_login');
        $user = $repo->find($userId);
        if (!$user) return $this->redirectToRoute('app_login');

        $error = null; $success = null;
        $dm = $em->getConnection()->getDatabasePlatform() ? $em : $em;

        // Load axes
        $prefs   = $dm->find(Preferences::class,   $userId);
        $axeV    = $dm->find(AxeVoyage::class,      $userId);
        $axeT    = $dm->find(AxeTransport::class,   $userId);
        $axeA    = $dm->find(AxeActivite::class,    $userId);
        $axeH    = $dm->find(AxeHebergement::class, $userId);

        if ($request->isMethod('POST')) {
            $section = $request->request->get('section');

            if ($section === 'avatar') {
                $file = $request->files->get('avatar');
                if ($file && $file->isValid()) {
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/avatars/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $ext = $file->guessExtension() ?? 'jpg';
                    $filename = 'avatar_'.$userId.'_'.uniqid().'.'.$ext;
                    $file->move($uploadDir, $filename);
                    $user->setPhotoDeProfil('/uploads/avatars/'.$filename);
                    $em->flush();
                    $success = 'Profile picture updated.';
                }
            }

            if ($section === 'personal') {
                $user->setPrenom(trim($request->request->get('prenom', $user->getPrenom())));
                $user->setNom(trim($request->request->get('nom', $user->getNom())));
                $user->setTelephone((int) $request->request->get('telephone', $user->getTelephone()));
                $user->setNationalite(trim($request->request->get('nationalite', '')) ?: null);
                $user->setAdresse(trim($request->request->get('adresse', '')) ?: null);
                $user->setVille(trim($request->request->get('ville', '')) ?: null);
                $user->setPays(trim($request->request->get('pays', '')) ?: null);
                $user->setCodePostale(trim($request->request->get('code_postale', '')) ?: null);
                $user->setBio(trim($request->request->get('bio', '')) ?: null);
                $dob = $request->request->get('date_naissance', '');
                if ($dob) $user->setDateNaissance(new \DateTime($dob));
                $em->flush();
                $request->getSession()->set('user_name', $user->getPrenom().' '.$user->getNom());
                $success = 'Profile updated successfully.';
            }

            if ($section === 'password') {
                $current = $request->request->get('current_password', '');
                $new     = $request->request->get('new_password', '');
                $confirm = $request->request->get('confirm_password', '');
                if (!password_verify($current, $user->getMotDePasse())) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } elseif ($new !== $confirm) {
                    $error = 'Passwords do not match.';
                } else {
                    $user->setMotDePasse(password_hash($new, PASSWORD_BCRYPT));
                    $em->flush();
                    $success = 'Password changed successfully.';
                }
            }

            if ($section === 'preferences') {
                // Ensure Preferences row exists
                if (!$prefs) {
                    $prefs = new Preferences();
                    $prefs->setClientId($userId);
                    $em->persist($prefs);
                    $em->flush();
                }
                $pid  = $prefs->getId();
                $axe  = $request->request->get('axe'); // which axe is being saved

                if ($axe === 'voyage' || !$axe) {
                    if (!$axeV) { $axeV = new AxeVoyage(); $axeV->setId($pid); $em->persist($axeV); }
                    $axeV->setTypesVoyages($request->request->get('types_voyages') ?: null)
                         ->setDestinations($request->request->get('destinations') ?: null)
                         ->setSaisonsPreferees($request->request->get('saisons') ?: null)
                         ->setDuree($request->request->get('duree') !== '' ? (int)$request->request->get('duree') : 0)
                         ->setBudgetMin($request->request->get('voyage_budget_min') !== '' ? (float)$request->request->get('voyage_budget_min') : 0)
                         ->setBudgetMax($request->request->get('voyage_budget_max') !== '' ? (float)$request->request->get('voyage_budget_max') : 0);
                }

                if ($axe === 'transport' || !$axe) {
                    if (!$axeT) { $axeT = new AxeTransport(); $axeT->setId($pid); $em->persist($axeT); }
                    $axeT->setTypeTransport($request->request->get('type_transport') ?: null)
                         ->setClasse($request->request->get('classe') ?: null)
                         ->setAccepteEscale($request->request->get('accepte_escale') ?: 'non')
                         ->setToleranceTemps($request->request->get('tolerance_temps') !== '' ? (int)$request->request->get('tolerance_temps') : 0)
                         ->setBudgetMin($request->request->get('transport_budget_min') !== '' ? (float)$request->request->get('transport_budget_min') : 0)
                         ->setBudgetMax($request->request->get('transport_budget_max') !== '' ? (float)$request->request->get('transport_budget_max') : 0);
                }

                if ($axe === 'activite' || !$axe) {
                    if (!$axeA) { $axeA = new AxeActivite(); $axeA->setId($pid); $em->persist($axeA); }
                    $axeA->setTypesActivite($request->request->get('types_activite') ?: null)
                         ->setNiveau($request->request->get('niveau') ?: null)
                         ->setAvecGuide($request->request->get('avec_guide') ?: 'non')
                         ->setAvecGroupe($request->request->get('avec_groupe') ?: 'non')
                         ->setBudgetMin($request->request->get('activite_budget_min') !== '' ? (float)$request->request->get('activite_budget_min') : 0)
                         ->setBudgetMax($request->request->get('activite_budget_max') !== '' ? (float)$request->request->get('activite_budget_max') : 0);
                }

                if ($axe === 'hebergement' || !$axe) {
                    if (!$axeH) { $axeH = new AxeHebergement(); $axeH->setId($pid); $em->persist($axeH); }
                    $axeH->setTypeHebergement($request->request->get('type_hebergement') ?: null)
                         ->setCategorieHotel($request->request->get('categorie_hotel') ?: null)
                         ->setServices($request->request->get('services') ?: null)
                         ->setAccepteColocation($request->request->get('accepte_colocation') ?: 'non')
                         ->setNombreDeChambre($request->request->get('nombre_chambre') !== '' ? (int)$request->request->get('nombre_chambre') : 1)
                         ->setBudgetMin($request->request->get('heberg_budget_min') !== '' ? (float)$request->request->get('heberg_budget_min') : 0)
                         ->setBudgetMax($request->request->get('heberg_budget_max') !== '' ? (float)$request->request->get('heberg_budget_max') : 0);
                }

                $em->flush();
                $success = 'Preferences saved.';
            }
        }

        return $this->render('dashboard/profile.html.twig', [
            'user'    => $user,
            'axeV'   => $axeV,
            'axeT'   => $axeT,
            'axeA'   => $axeA,
            'axeH'   => $axeH,
            'error'   => $error,
            'success' => $success,
        ]);
    }

    #[Route('/agent-dashboard', name: 'app_agent_dashboard')]
    public function agentDashboard(): Response
    {
        return $this->render('dashboard/agent.html.twig');
    }

    #[Route('/admin-dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(): Response
    {
        return $this->render('dashboard/admin.html.twig');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function userManagement(Request $request, UtilisateurRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', '');

        $qb = $repo->createQueryBuilder('u');
        if ($search) {
            $qb->andWhere('u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }
        if ($role) {
            $qb->andWhere('u.role = :r')->setParameter('r', $role);
        }
        $users = $qb->orderBy('u.id', 'DESC')->getQuery()->getResult();

        $all     = $repo->count([]);
        $active  = (int) $repo->createQueryBuilder('u')->select('COUNT(u.id)')->where('LOWER(u.statut) = :s')->setParameter('s','actif')->getQuery()->getSingleScalarResult();
        $banned  = (int) $repo->createQueryBuilder('u')->select('COUNT(u.id)')->where('LOWER(u.statut) = :s')->setParameter('s','suspendu')->getQuery()->getSingleScalarResult();
        $pending = (int) $repo->createQueryBuilder('u')->select('COUNT(u.id)')->where('LOWER(u.statut) = :s')->setParameter('s','en_attente')->getQuery()->getSingleScalarResult();

        return $this->render('dashboard/admin_users.html.twig', [
            'users'   => $users,
            'search'  => $search,
            'role'    => $role,
            'total'   => $all,
            'active'  => $active,
            'banned'  => $banned,
            'pending' => $pending,
        ]);
    }

    #[Route('/admin/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(int $id, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->find($id);
        if ($user) {
            $user->setStatut('suspendu');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users/{id}/reactivate', name: 'app_admin_user_reactivate', methods: ['POST'])]
    public function reactivateUser(int $id, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->find($id);
        if ($user) {
            $user->setStatut('actif');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/profile/avatar/dicebear', name: 'app_profile_dicebear', methods: ['POST'])]
    public function saveDicebearAvatar(Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['error' => 'Not logged in'], 401);

        $url = $request->request->get('url', '');
        if (!str_starts_with($url, 'https://api.dicebear.com/')) {
            return $this->json(['error' => 'Invalid avatar URL'], 400);
        }

        $user = $repo->find($userId);
        if ($user) {
            $user->setPhotoDeProfil($url);
            $em->flush();
        }

        return $this->json(['status' => 'done', 'url' => $url]);
    }

    #[Route('/admin/partnerships/{id}/analyse', name: 'app_admin_partnership_analyse', methods: ['POST'])]
    public function analysePartnership(int $id, ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $agent = $doctrine->getManager()->find(Agent::class, $id);
        if (!$agent) return $this->json(['error' => 'Agent not found'], 404);

        $agentInfo = [
            'Agency Name'      => $agent->getNomAgence(),
            'Legal Name'       => $agent->getNomLegalAgence(),
            'Email'            => $agent->getEmailAgence(),
            'Phone'            => $agent->getTelephoneAgence(),
            'Country'          => $agent->getPaysAgence(),
            'City'             => $agent->getVilleAgence(),
            'Address'          => $agent->getAdresseAgence(),
            'Trade Register'   => $agent->getNumeroRegistreCommerce(),
            'Tax Number'       => $agent->getNumeroFiscal(),
            'Licence Number'   => $agent->getNumeroLicenceAgence(),
        ];

        $docs = [
            'Trade Register'   => $agent->getDocRegistreCommerceUrl(),
            'Tax Certificate'  => $agent->getDocMatriculeFiscalUrl(),
            'Agency Licence'   => $agent->getDocLicenceAgenceUrl(),
            'ID Card (Front)'  => $agent->getDocPieceIdentiteRectoUrl(),
            'ID Card (Back)'   => $agent->getDocPieceIdentiteVersoUrl(),
            'Proof of Address' => $agent->getDocJustificatifAdresseUrl(),
            'Bank Details'     => $agent->getDocRibOuReleveBancaireUrl(),
        ];
        if ($agent->getDocAssuranceUrl() && $agent->getDocAssuranceUrl() !== 'pending') {
            $docs['Insurance'] = $agent->getDocAssuranceUrl();
        }

        $results = [];
        $apiKey  = $_ENV['GEMINI_API_KEY'] ?? '';
        $apiUrl  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key='.$apiKey;
        $baseDir = $this->getParameter('kernel.project_dir').'/public';

        foreach ($docs as $docType => $docPath) {
            if (!$docPath || $docPath === 'pending') {
                $results[$docType] = ['status' => 'review', 'confidence' => 0, 'summary' => 'Document not uploaded', 'reasons' => ['No file provided'], 'risk_flags' => ['Missing document']];
                continue;
            }

            // Load file — local file or URL
            $normalizedDoc = str_replace('\\', '/', $docPath); // normalize backslashes
            if (!str_starts_with($normalizedDoc, '/')) {
                $normalizedDoc = '/' . $normalizedDoc; // ensure leading slash
            }
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $normalizedDoc), DIRECTORY_SEPARATOR);
            $imageData = @file_get_contents($fullPath);
            if (!$imageData) {
                $results[$docType] = [
                    'status' => 'review', 'confidence' => 0,
                    'summary' => 'Document file not found on server',
                    'reasons' => ['The file was uploaded from another machine and is not available here'],
                    'risk_flags' => ['Missing file: ' . basename($fullPath)]
                ];
                continue;
            }

            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

            // Gemini Vision only supports images — convert PDF first page to JPEG if needed
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf']) || str_contains($mimeType, 'pdf')) {
                // Try Imagick if available
                if (extension_loaded('imagick')) {
                    try {
                        $im = new \Imagick();
                        $im->setResolution(150, 150);
                        $im->readImage($fullPath . '[0]'); // first page only
                        $im->setImageFormat('jpeg');
                        $im->setImageCompressionQuality(85);
                        $imageData = $im->getImageBlob();
                        $mimeType  = 'image/jpeg';
                        $im->destroy();
                    } catch (\Exception $e) {
                        // Imagick failed — send as PDF inline data
                        $mimeType = 'application/pdf';
                    }
                } else {
                    // No Imagick — send PDF as inline_data, Gemini 1.5+ supports it
                    $mimeType = 'application/pdf';
                }
            }
            $b64      = base64_encode($imageData);

            // Build Gemini prompt
            $agentInfoStr = implode("\n", array_map(fn($k,$v) => "- $k: $v", array_keys($agentInfo), $agentInfo));
            $prompt = "You are an automated document verification AI for a travel partnership system.\n\n"
                ."Document Type: $docType\n\n"
                ."Agent Information:\n$agentInfoStr\n\n"
                ."CRITICAL INSTRUCTIONS:\n"
                ."1. Analyze the document image for validity and consistency.\n"
                ."2. Compare the document content with the agent's submitted information.\n"
                ."3. Check for errors, mismatches, missing data, or suspicious content.\n\n"
                ."4. Decide on a preliminary status:\n"
                ."   - \"approved\" if everything is clearly valid and matches\n"
                ."   - \"rejected\" if major inconsistencies, mismatches, or invalid information found\n"
                ."   - \"review\" if unclear or partially valid\n\n"
                ."5. CONFIDENCE SCORE RULES:\n"
                ."   - HIGH (80-100): Document is VALID, information matches, looks legitimate\n"
                ."   - MEDIUM (40-79): Document is UNCLEAR, needs review\n"
                ."   - LOW (0-39): Document is INVALID, major mismatches, suspicious\n\n"
                ."Return ONLY valid JSON (no markdown):\n"
                ."{\n"
                ."  \"status\": \"approved | rejected | review\",\n"
                ."  \"confidence\": number (0-100),\n"
                ."  \"summary\": \"short explanation\",\n"
                ."  \"reasons\": [\"reason1\", \"reason2\"],\n"
                ."  \"risk_flags\": [\"flag1\"]\n"
                ."}";

            // Call Gemini Vision API
            $payload = json_encode([
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => $mimeType, 'data' => $b64]],
                    ]
                ]]
            ]);

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 60,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            $gemini = json_decode($response, true);
            $aiText = $gemini['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$aiText) {
                $geminiError = $gemini['error']['message'] ?? ('No response. HTTP payload size: ' . strlen($payload ?? '') . ' bytes');
                $results[$docType] = ['status' => 'review', 'confidence' => 0, 'summary' => 'AI analysis failed', 'reasons' => [$geminiError], 'risk_flags' => []];
                continue;
            }

            // Clean markdown fences
            $aiText = preg_replace('/^```json\s*/m', '', trim($aiText));
            $aiText = preg_replace('/^```\s*/m', '', $aiText);
            $aiText = rtrim($aiText, '`');

            $parsed = json_decode(trim($aiText), true);
            if (!$parsed) {
                $results[$docType] = ['status' => 'review', 'confidence' => 0, 'summary' => 'Could not parse AI response', 'reasons' => [], 'risk_flags' => []];
                continue;
            }

            // Validate confidence matches status (same logic as Java)
            $status     = strtolower($parsed['status'] ?? 'review');
            $confidence = (int)($parsed['confidence'] ?? 0);
            if ($status === 'approved' && $confidence < 60)  $confidence = 70;
            if ($status === 'rejected' && $confidence > 40)  $confidence = 25;
            if ($status === 'review'   && $confidence < 30)  $confidence = 35;
            if ($status === 'review'   && $confidence > 70)  $confidence = 60;

            $results[$docType] = [
                'status'     => $status,
                'confidence' => $confidence,
                'summary'    => $parsed['summary']    ?? '',
                'reasons'    => $parsed['reasons']    ?? [],
                'risk_flags' => $parsed['risk_flags'] ?? [],
            ];
        }

        // Overall report
        $avgConfidence = count($results) ? (int)(array_sum(array_column($results, 'confidence')) / count($results)) : 0;
        $statuses = array_column($results, 'status');
        $overall  = in_array('rejected', $statuses) ? 'rejected' : (in_array('review', $statuses) ? 'review' : 'approved');

        return $this->json([
            'overall'    => $overall,
            'confidence' => $avgConfidence,
            'documents'  => $results,
        ]);
    }
    #[Route('/admin/partnerships', name: 'app_admin_partnerships')]
    public function partnerships(EntityManagerInterface $em): Response
    {
        $pending  = $em->createQueryBuilder()
            ->select('a')->from(Agent::class, 'a')
            ->where("LOWER(a.statutVerification) = 'en_attente'")
            ->orderBy('a.dateSoumission', 'DESC')
            ->getQuery()->getResult();

        $inReview = $em->createQueryBuilder()
            ->select('a')->from(Agent::class, 'a')
            ->where("LOWER(a.statutVerification) = 'en_cours'")
            ->orderBy('a.dateSoumission', 'DESC')
            ->getQuery()->getResult();

        $approved = $em->createQueryBuilder()
            ->select('a')->from(Agent::class, 'a')
            ->where("LOWER(a.statutVerification) = 'valide'")
            ->orderBy('a.dateValidation', 'DESC')
            ->getQuery()->getResult();

        $refused  = $em->createQueryBuilder()
            ->select('a')->from(Agent::class, 'a')
            ->where("LOWER(a.statutVerification) = 'refuse'")
            ->orderBy('a.dateSoumission', 'DESC')
            ->getQuery()->getResult();

        return $this->render('dashboard/admin_partnerships.html.twig', [
            'pending'   => $pending,
            'inReview'  => $inReview,
            'approved'  => $approved,
            'refused'   => $refused,
        ]);
    }

    #[Route('/admin/partnerships/{id}/review', name: 'app_admin_partnership_review', methods: ['POST'])]
    public function startReview(int $id, EntityManagerInterface $em): Response
    {
        $agent = $em->find(Agent::class, $id);
        if ($agent) {
            $agent->setStatutVerification('en_cours');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_partnerships', ['tab' => 'review']);
    }

    #[Route('/admin/partnerships/{id}/approve', name: 'app_admin_partnership_approve', methods: ['POST'])]
    public function approvePartnership(int $id, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $agent = $em->find(Agent::class, $id);
        if ($agent) {
            $agent->setStatutVerification('valide');
            $agent->setEstSuspendu('non');
            $agent->setDateValidation(new \DateTime());
            $user = $userRepo->find($id);
            if ($user) $user->setStatut('actif');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_partnerships', ['tab' => 'partners']);
    }

    #[Route('/admin/partnerships/{id}/deny', name: 'app_admin_partnership_deny', methods: ['POST'])]
    public function denyPartnership(int $id, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $agent = $em->find(Agent::class, $id);
        if ($agent) {
            $agent->setStatutVerification('refuse');
            $agent->setEstSuspendu('non');
            $user = $userRepo->find($id);
            if ($user) $user->setStatut('suspendu');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_partnerships', ['tab' => 'refused']);
    }

    #[Route('/admin/partnerships/{id}/revoke', name: 'app_admin_partnership_revoke', methods: ['POST'])]
    public function revokePartnership(int $id, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $agent = $em->find(Agent::class, $id);
        if ($agent) {
            $agent->setStatutVerification('refuse');
            $agent->setEstSuspendu('oui');
            $agent->setDateSuspension(new \DateTime());
            $user = $userRepo->find($id);
            if ($user) $user->setStatut('suspendu');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_partnerships', ['tab' => 'refused']);
    }

    #[Route('/admin/partnerships/{id}/reopen', name: 'app_admin_partnership_reopen', methods: ['POST'])]
    public function reopenPartnership(int $id, EntityManagerInterface $em): Response
    {
        $agent = $em->find(Agent::class, $id);
        if ($agent) {
            $agent->setStatutVerification('en_cours');
            $agent->setEstSuspendu('non');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_partnerships', ['tab' => 'review']);
    }

    #[Route('/admin/coupons', name: 'app_admin_coupons')]
    public function coupons(Request $request, ManagerRegistry $doctrine, UtilisateurRepository $userRepo, EntityManagerInterface $em): Response
    {
        $em2 = $doctrine->getManager();

        // Handle coupon generation
        if ($request->isMethod('POST')) {
            $qty        = max(1, (int) $request->request->get('quantity', 1));
            $prefix     = strtoupper(trim($request->request->get('prefix', 'COUP')));
            $type       = $request->request->get('type', 'REDUCTION_POURCENTAGE');
            $discount   = (float) $request->request->get('discount', 10);
            $minOrder   = (float) $request->request->get('min_order', 0);
            $expiration = $request->request->get('expiration', date('Y-m-d', strtotime('+30 days')));
            $actif      = $request->request->get('actif') ? 'oui' : 'non';

            for ($i = 0; $i < $qty; $i++) {
                $coupon = new Coupon();
                $coupon->setCode($prefix . rand(100000, 999999))
                       ->setType($type)
                       ->setPourcentage($discount)
                       ->setValeurReduction($discount)
                       ->setMontantMinimum($minOrder)
                       ->setDateExpiration(new \DateTime($expiration))
                       ->setDateCreation(new \DateTime())
                       ->setActif($actif)
                       ->setClientId(0);
                $em->persist($coupon);
            }
            $em->flush();
            return $this->redirectToRoute('app_admin_coupons');
        }

        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', '');

        $qb = $em2->createQueryBuilder()->select('c')->from(Coupon::class, 'c');
        if ($search) $qb->andWhere('c.code LIKE :s OR c.type LIKE :s')->setParameter('s', '%'.$search.'%');
        if ($filter === 'active')   $qb->andWhere("LOWER(c.actif) = 'oui'")->andWhere('c.dateExpiration >= :now')->setParameter('now', new \DateTime());
        if ($filter === 'expired')  $qb->andWhere('c.dateExpiration < :now')->setParameter('now', new \DateTime());
        if ($filter === 'assigned') $qb->andWhere('c.clientId > 0');
        $coupons = $qb->orderBy('c.id', 'DESC')->getQuery()->getResult();

        $total    = $em2->createQueryBuilder()->select('COUNT(c.id)')->from(Coupon::class,'c')->getQuery()->getSingleScalarResult();
        $active   = $em2->createQueryBuilder()->select('COUNT(c.id)')->from(Coupon::class,'c')->where("LOWER(c.actif)='oui'")->andWhere('c.dateExpiration >= :now')->setParameter('now', new \DateTime())->getQuery()->getSingleScalarResult();
        $expired  = $em2->createQueryBuilder()->select('COUNT(c.id)')->from(Coupon::class,'c')->where('c.dateExpiration < :now')->setParameter('now', new \DateTime())->getQuery()->getSingleScalarResult();
        $assigned = $em2->createQueryBuilder()->select('COUNT(c.id)')->from(Coupon::class,'c')->where('c.clientId > 0')->getQuery()->getSingleScalarResult();

        // Build clientId -> name map for assigned coupons
        $clientIds = array_filter(array_unique(array_map(fn($c) => $c->getClientId(), $coupons)), fn($id) => $id > 0);
        $users = [];
        if ($clientIds) {
            $rows = $userRepo->createQueryBuilder('u')->where('u.id IN (:ids)')->setParameter('ids', array_values($clientIds))->getQuery()->getResult();
            foreach ($rows as $u) $users[$u->getId()] = $u->getPrenom().' '.$u->getNom();
        }

        return $this->render('dashboard/admin_coupons.html.twig', [
            'coupons'  => $coupons,
            'users'    => $users,
            'search'   => $search,
            'filter'   => $filter,
            'total'    => (int)$total,
            'active'   => (int)$active,
            'expired'  => (int)$expired,
            'assigned' => (int)$assigned,
            'defaultExpiry' => date('Y-m-d', strtotime('+30 days')),
        ]);
    }

    #[Route('/admin/coupons/{id}/delete', name: 'app_admin_coupon_delete', methods: ['POST'])]
    public function deleteCoupon(int $id, ManagerRegistry $doctrine, EntityManagerInterface $em): Response
    {
        $coupon = $doctrine->getManager()->find(Coupon::class, $id);
        if ($coupon) { $em->remove($coupon); $em->flush(); }
        return $this->redirectToRoute('app_admin_coupons');
    }

    #[Route('/admin/coupons/{id}/toggle', name: 'app_admin_coupon_toggle', methods: ['POST'])]
    public function toggleCoupon(int $id, ManagerRegistry $doctrine, EntityManagerInterface $em): Response
    {
        $coupon = $doctrine->getManager()->find(Coupon::class, $id);
        if ($coupon) {
            $coupon->setActif($coupon->getActif() === 'oui' ? 'non' : 'oui');
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_coupons');
    }
}
