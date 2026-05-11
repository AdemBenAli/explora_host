<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Entity\VerificationCodes;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $email    = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $utilisateurRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $error = 'No account found with that email.';
            } elseif (!password_verify($password, $user->getMotDePasse())) {
                $error = 'Invalid password. Please try again.';
            } else {
                // Store user info in session
                $session = $request->getSession();
                $session->set('user_id',   $user->getId());
                $session->set('user_name', $user->getPrenom() . ' ' . $user->getNom());
                $session->set('user_role', $user->getRole());
                $session->set('user_email', $user->getEmail());

                // Redirect by role
                $role = strtolower($user->getRole());
                return match($role) {
                    'admin'   => $this->redirectToRoute('app_admin_dashboard'),
                    'agent'   => $this->redirectToRoute('app_agent_dashboard'),
                    default   => $this->redirectToRoute('app_traveler_dashboard'),
                };
            }
        }

        return $this->render('security/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response
    {
        return $this->render('security/signup.html.twig');
    }

    #[Route('/signup/traveler', name: 'app_signup_traveler', methods: ['GET', 'POST'])]
    public function signupTraveler(Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $prenom    = trim($request->request->get('prenom', ''));
            $nom       = trim($request->request->get('nom', ''));
            $email     = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $password  = $request->request->get('password', '');
            $confirm   = $request->request->get('confirm_password', '');

            if (!$prenom || !$nom || !$email || !$telephone || !$password) {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } elseif ($utilisateurRepository->findOneBy(['email' => $email])) {
                $error = 'An account with this email already exists.';
            } else {
                $user = new Utilisateur();
                $user->setPrenom($prenom)->setNom($nom)->setEmail($email)
                     ->setTelephone((int) $telephone)
                     ->setMotDePasse(password_hash($password, PASSWORD_BCRYPT))
                     ->setRole('voyageur')->setStatut('en_attente')
                     ->setEstVerifie('non')->setDateCreation(new \DateTime());
                $em->persist($user);
                $em->flush();

                // Generate 6-digit code and save via raw SQL (avoids MySQL reserved word 'type')
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiry = (new \DateTime('+15 minutes'))->format('Y-m-d H:i:s');
                $now    = (new \DateTime())->format('Y-m-d H:i:s');
                $em->getConnection()->executeStatement(
                    'INSERT INTO verification_codes (email, code, expiration_time, created_at, is_used) VALUES (?, ?, ?, ?, 0)',
                    [$email, $code, $expiry, $now]
                );

                // Send email
                $emailMsg = (new Email())
                    ->from('saadaouilouay16@gmail.com')
                    ->to($email)
                    ->subject('Explora — Verify your email')
                    ->html(
                        '<div style="font-family:Inter,sans-serif;max-width:480px;margin:auto;padding:32px;background:#f8fafc;border-radius:12px;">
                            <h2 style="color:#1e5faf;margin-bottom:8px;">Welcome to Explora, '.$prenom.'!</h2>
                            <p style="color:#64748b;margin-bottom:24px;">Use the code below to verify your email address. It expires in <strong>15 minutes</strong>.</p>
                            <div style="background:#1e5faf;color:white;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:10px;">'.$code.'</div>
                            <p style="color:#94a3b8;font-size:12px;margin-top:24px;">If you did not create an account, ignore this email.</p>
                        </div>'
                    );
                $mailer->send($emailMsg);

                // Store email in session for the verify page
                $request->getSession()->set('pending_verification_email', $email);
                $method = $request->request->get('verification', 'email');

                if ($method === 'sms') {
                    // Use Twilio Verify for SMS
                    $phone = '+216' . ltrim((string)((int)$telephone), '+');
                    $twilio = new \Twilio\Rest\Client(
                        $_ENV['TWILIO_ACCOUNT_SID'],
                        $_ENV['TWILIO_AUTH_TOKEN']
                    );
                    $twilio->verify->v2->services($_ENV['TWILIO_VERIFY_SID'])
                        ->verifications->create($phone, 'sms');
                    $request->getSession()->set('pending_verification_phone', $phone);
                    return $this->redirectToRoute('app_verify_sms');
                }

                // Default: email verification

                return $this->redirectToRoute('app_verify_email');
            }
        }

        return $this->render('security/signup_traveler.html.twig', ['error' => $error]);
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET', 'POST'])]
    public function verifyEmail(Request $request, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $email = $request->getSession()->get('pending_verification_email');
        if (!$email) return $this->redirectToRoute('app_signup');

        $error = null;

        if ($request->isMethod('POST')) {
            $entered = trim($request->request->get('code', ''));

            $row = $em->getConnection()->fetchAssociative(
                'SELECT * FROM verification_codes WHERE email = ? AND (is_used = 0 OR is_used IS NULL) AND expiration_time > ? ORDER BY id DESC LIMIT 1',
                [$email, (new \DateTime())->format('Y-m-d H:i:s')]
            );

            // DEBUG — remove after fixing
            if (!$row) {
                $all = $em->getConnection()->fetchAllAssociative(
                    'SELECT id, email, code, `type`, expiration_time, is_used FROM verification_codes WHERE email = ? ORDER BY id DESC LIMIT 3',
                    [$email]
                );
                $error = 'No valid code found. DB rows: '.json_encode($all).' | Entered: "'.$entered.'" | Now: '.(new \DateTime())->format('Y-m-d H:i:s');
            } elseif (trim($row['code']) !== trim($entered)) {
                $error = 'Code mismatch. DB: "'.$row['code'].'" ('.strlen($row['code']).') | Entered: "'.$entered.'" ('.strlen($entered).')';
            } else {
                $em->getConnection()->executeStatement(
                    'UPDATE verification_codes SET is_used = 1 WHERE id = ?',
                    [$row['id']]
                );
                $user = $userRepo->findOneBy(['email' => $email]);
                if ($user) {
                    $user->setEstVerifie('oui')->setStatut('actif');
                    $request->getSession()->set('user_id',    $user->getId());
                    $request->getSession()->set('user_name',  $user->getPrenom().' '.$user->getNom());
                    $request->getSession()->set('user_role',  $user->getRole());
                    $request->getSession()->set('user_email', $user->getEmail());
                    $em->flush();

                    // Create Client record (inheritance)
                    $existing = $em->find(Client::class, $user->getId());
                    if (!$existing) {
                        $client = new Client();
                        $client->setId($user->getId())
                               ->setScoreFidelite(0)
                               ->setBadge('Bronze')
                               ->setAdresse($user->getAdresse())
                               ->setVille($user->getVille())
                               ->setPaysResidence($user->getPays());
                        $em->persist($client);
                        $em->flush();
                    }
                }
                $request->getSession()->remove('pending_verification_email');
                $role = strtolower($user ? $user->getRole() : 'voyageur');
                return match($role) {
                    'admin' => $this->redirectToRoute('app_admin_dashboard'),
                    'agent' => $this->redirectToRoute('app_signup_agent_success'),
                    default => $this->redirectToRoute('app_traveler_dashboard'),
                };
            }
        }

        return $this->render('security/verify_email.html.twig', [
            'email' => $email,
            'error' => $error,
        ]);
    }

    #[Route('/verify-sms', name: 'app_verify_sms', methods: ['GET', 'POST'])]
    public function verifySms(Request $request, UtilisateurRepository $userRepo, EntityManagerInterface $em): Response
    {
        $email = $request->getSession()->get('pending_verification_email');
        $phone = $request->getSession()->get('pending_verification_phone');
        if (!$email || !$phone) return $this->redirectToRoute('app_signup');

        $error = null;

        if ($request->isMethod('POST')) {
            $entered = trim($request->request->get('code', ''));

            try {
                $twilio = new \Twilio\Rest\Client(
                    $_ENV['TWILIO_ACCOUNT_SID'],
                    $_ENV['TWILIO_AUTH_TOKEN']
                );
                $check = $twilio->verify->v2->services($_ENV['TWILIO_VERIFY_SID'])
                    ->verificationChecks->create(['to' => $phone, 'code' => $entered]);

                if ($check->status === 'approved') {
                    $user = $userRepo->findOneBy(['email' => $email]);
                    if ($user) {
                        $user->setEstVerifie('oui')->setStatut('actif');
                        $request->getSession()->set('user_id',    $user->getId());
                        $request->getSession()->set('user_name',  $user->getPrenom().' '.$user->getNom());
                        $request->getSession()->set('user_role',  $user->getRole());
                        $request->getSession()->set('user_email', $user->getEmail());
                        $em->flush();

                        $existing = $em->find(Client::class, $user->getId());
                        if (!$existing) {
                            $client = new Client();
                            $client->setId($user->getId())->setScoreFidelite(0)->setBadge('Bronze')
                                   ->setAdresse($user->getAdresse())->setVille($user->getVille())
                                   ->setPaysResidence($user->getPays());
                            $em->persist($client);
                            $em->flush();
                        }
                    }
                    $request->getSession()->remove('pending_verification_email');
                    $request->getSession()->remove('pending_verification_phone');
                    $role = strtolower($user ? $user->getRole() : 'voyageur');
                    return match($role) {
                        'admin' => $this->redirectToRoute('app_admin_dashboard'),
                        'agent' => $this->redirectToRoute('app_signup_agent_success'),
                        default => $this->redirectToRoute('app_traveler_dashboard'),
                    };
                } else {
                    $error = 'Invalid code. Please try again.';
                }
            } catch (\Exception $e) {
                $error = 'Verification failed: ' . $e->getMessage();
            }
        }

        return $this->render('security/verify_sms.html.twig', [
            'phone' => preg_replace('/(\d{3})(\d+)(\d{2})$/', '$1****$3', $phone),
            'error' => $error,
        ]);
    }

    #[Route('/verify-sms/resend', name: 'app_verify_sms_resend', methods: ['POST'])]
    public function resendSms(Request $request): Response
    {
        $phone = $request->getSession()->get('pending_verification_phone');
        if (!$phone) return $this->redirectToRoute('app_signup');

        $twilio = new \Twilio\Rest\Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']);
        $twilio->verify->v2->services($_ENV['TWILIO_VERIFY_SID'])
            ->verifications->create($phone, 'sms');

        return $this->redirectToRoute('app_verify_sms');
    }

    #[Route('/verify-email/resend', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $em, MailerInterface $mailer, UtilisateurRepository $userRepo): Response
    {
        $email = $request->getSession()->get('pending_verification_email');
        if (!$email) return $this->redirectToRoute('app_signup');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user = $userRepo->findOneBy(['email' => $email]);
        $prenom = $user ? $user->getPrenom() : 'there';
        $expiry = (new \DateTime('+15 minutes'))->format('Y-m-d H:i:s');
        $now    = (new \DateTime())->format('Y-m-d H:i:s');
        $em->getConnection()->executeStatement(
            'INSERT INTO verification_codes (email, code, expiration_time, created_at, is_used) VALUES (?, ?, ?, ?, 0)',
            [$email, $code, $expiry, $now]
        );

        $mailer->send((new Email())
            ->from('saadaouilouay16@gmail.com')->to($email)
            ->subject('Explora — New verification code')
            ->html('<div style="font-family:Inter,sans-serif;max-width:480px;margin:auto;padding:32px;background:#f8fafc;border-radius:12px;">
                <h2 style="color:#1e5faf;">Hi '.$prenom.', here\'s your new code</h2>
                <div style="background:#1e5faf;color:white;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:10px;">'.$code.'</div>
                <p style="color:#94a3b8;font-size:12px;margin-top:16px;">Expires in 15 minutes.</p>
            </div>')
        );

        return $this->redirectToRoute('app_verify_email');
    }

    #[Route('/signup/agent', name: 'app_signup_agent', methods: ['GET', 'POST'])]
    public function signupAgent(Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            // Personal info
            $prenom   = trim($request->request->get('prenom', ''));
            $nom      = trim($request->request->get('nom', ''));
            $email    = trim($request->request->get('email', ''));
            $tel      = trim($request->request->get('telephone', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm_password', '');

            // Agency info
            $nomAgence   = trim($request->request->get('nom_agence', ''));
            $nomLegal    = trim($request->request->get('nom_legal_agence', ''));
            $descAgence  = trim($request->request->get('description_agence', ''));
            $emailAgence = trim($request->request->get('email_agence', ''));
            $telAgence   = trim($request->request->get('telephone_agence', ''));
            $pays        = trim($request->request->get('pays_agence', ''));
            $ville       = trim($request->request->get('ville_agence', ''));
            $adresse     = trim($request->request->get('adresse_agence', ''));
            $codePostal  = trim($request->request->get('code_postal_agence', ''));
            $siteWeb     = trim($request->request->get('site_web', ''));

            // Legal info
            $registreCommerce = trim($request->request->get('numero_registre_commerce', ''));
            $numeroFiscal     = trim($request->request->get('numero_fiscal', ''));
            $licenceAgence    = trim($request->request->get('numero_licence_agence', ''));
            $dateEnreg        = $request->request->get('date_enregistrement', '');

            // Validate step 1
            if (!$prenom || !$nom || !$email || !$tel || !$password) {
                $error = ['step' => 1, 'msg' => 'Please fill in all personal information fields.'];
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = ['step' => 1, 'msg' => 'Invalid email address.'];
            } elseif (strlen($password) < 6) {
                $error = ['step' => 1, 'msg' => 'Password must be at least 6 characters.'];
            } elseif ($password !== $confirm) {
                $error = ['step' => 1, 'msg' => 'Passwords do not match.'];
            } elseif ($utilisateurRepository->findOneBy(['email' => $email])) {
                $error = ['step' => 1, 'msg' => 'An account with this email already exists.'];
            // Validate step 2
            } elseif (!$nomAgence || !$nomLegal || !$emailAgence || !$telAgence || !$pays || !$ville || !$adresse) {
                $error = ['step' => 2, 'msg' => 'Please fill in all agency information fields.'];
            } elseif (!filter_var($emailAgence, FILTER_VALIDATE_EMAIL)) {
                $error = ['step' => 2, 'msg' => 'Invalid agency email address.'];
            } elseif ($em->getConnection()->fetchOne('SELECT COUNT(*) FROM agent WHERE nomAgence = ?', [$nomAgence]) > 0) {
                $error = ['step' => 2, 'msg' => 'An agency with this name already exists. Please choose a different name.'];
            // Validate step 3
            } elseif (!$registreCommerce || !$numeroFiscal || !$licenceAgence || !$dateEnreg) {
                $error = ['step' => 3, 'msg' => 'Please fill in all legal information fields.'];
            } else {
                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/agents/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $docs = [
                        'doc_registre_commerce'   => 'docRegistreCommerceUrl',
                        'doc_matricule_fiscal'     => 'docMatriculeFiscalUrl',
                        'doc_licence_agence'       => 'docLicenceAgenceUrl',
                        'doc_piece_identite_recto' => 'docPieceIdentiteRectoUrl',
                        'doc_piece_identite_verso' => 'docPieceIdentiteVersoUrl',
                        'doc_justificatif_adresse' => 'docJustificatifAdresseUrl',
                        'doc_rib_bancaire'         => 'docRibOuReleveBancaireUrl',
                        'doc_assurance'            => 'docAssuranceUrl',
                    ];

                    $uploadedPaths = [];
                    foreach ($docs as $field => $key) {
                        $file = $request->files->get($field);
                        if ($file && $file->isValid()) {
                            $filename = uniqid() . '_' . $file->getClientOriginalName();
                            $file->move($uploadDir, $filename);
                            $uploadedPaths[$key] = '/uploads/agents/' . $filename;
                        } else {
                            $uploadedPaths[$key] = 'pending';
                        }
                    }

                    $em->beginTransaction();

                    $user = new Utilisateur();
                    $user->setPrenom($prenom)->setNom($nom)->setEmail($email)
                         ->setTelephone((int)$tel)
                         ->setMotDePasse(password_hash($password, PASSWORD_BCRYPT))
                         ->setRole('agent')->setStatut('en_attente')
                         ->setEstVerifie('non')->setDateCreation(new \DateTime());
                    $em->persist($user);
                    $em->flush(); // flush to get the generated ID

                    $agent = new Agent();
                    $agent->setId($user->getId())
                          ->setNomAgence($nomAgence)->setNomLegalAgence($nomLegal)
                          ->setDescriptionAgence($descAgence ?: null)
                          ->setEmailAgence($emailAgence)->setTelephoneAgence($telAgence)
                          ->setPaysAgence($pays)->setVilleAgence($ville)
                          ->setAdresseAgence($adresse)->setCodePostalAgence($codePostal ?: null)
                          ->setSiteWebUrl($siteWeb ?: null)
                          ->setNumeroRegistreCommerce($registreCommerce)
                          ->setNumeroFiscal($numeroFiscal)->setNumeroLicenceAgence($licenceAgence)
                          ->setDateEnregistrement(new \DateTime($dateEnreg))
                          ->setDateSoumission(new \DateTime())
                          ->setStatutVerification('en_attente')->setEstSuspendu('non')
                          ->setDocRegistreCommerceUrl($uploadedPaths['docRegistreCommerceUrl'])
                          ->setDocMatriculeFiscalUrl($uploadedPaths['docMatriculeFiscalUrl'])
                          ->setDocLicenceAgenceUrl($uploadedPaths['docLicenceAgenceUrl'])
                          ->setDocPieceIdentiteRectoUrl($uploadedPaths['docPieceIdentiteRectoUrl'])
                          ->setDocPieceIdentiteVersoUrl($uploadedPaths['docPieceIdentiteVersoUrl'])
                          ->setDocJustificatifAdresseUrl($uploadedPaths['docJustificatifAdresseUrl'])
                          ->setDocRibOuReleveBancaireUrl($uploadedPaths['docRibOuReleveBancaireUrl'])
                          ->setDocAssuranceUrl($uploadedPaths['docAssuranceUrl']);
                    $em->persist($agent);
                    $em->flush();

                    $em->commit();

                    // Send verification
                    $method = $request->request->get('verification', 'email');
                    $request->getSession()->set('pending_verification_email', $email);

                    if ($method === 'sms') {
                        $phone = '+216' . ltrim((string)((int)$tel), '+');
                        $twilio = new \Twilio\Rest\Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']);
                        $twilio->verify->v2->services($_ENV['TWILIO_VERIFY_SID'])
                            ->verifications->create($phone, 'sms');
                        $request->getSession()->set('pending_verification_phone', $phone);
                        return $this->redirectToRoute('app_verify_sms');
                    }

                    // Email verification
                    $code   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expiry = (new \DateTime('+15 minutes'))->format('Y-m-d H:i:s');
                    $now    = (new \DateTime())->format('Y-m-d H:i:s');
                    $em->getConnection()->executeStatement(
                        'INSERT INTO verification_codes (email, code, expiration_time, created_at, is_used) VALUES (?, ?, ?, ?, 0)',
                        [$email, $code, $expiry, $now]
                    );
                    $mailer->send((new Email())
                        ->from('saadaouilouay16@gmail.com')->to($email)
                        ->subject('Explora — Verify your email')
                        ->html('<div style="font-family:Inter,sans-serif;max-width:480px;margin:auto;padding:32px;background:#f8fafc;border-radius:12px;">
                            <h2 style="color:#1e5faf;margin-bottom:8px;">Welcome to Explora, '.$prenom.'!</h2>
                            <p style="color:#64748b;margin-bottom:24px;">Use the code below to verify your email. Expires in <strong>15 minutes</strong>.</p>
                            <div style="background:#1e5faf;color:white;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:10px;">'.$code.'</div>
                        </div>')
                    );
                    return $this->redirectToRoute('app_verify_email');

                } catch (\Exception $e) {
                    if ($em->getConnection()->isTransactionActive()) {
                        $em->rollback();
                    }
                    $error = ['step' => 4, 'msg' => 'Registration failed: ' . $e->getMessage()];
                }
            }
        }

        return $this->render('security/signup_agent.html.twig', ['error' => $error]);
    }

    #[Route('/signup/agent/success', name: 'app_signup_agent_success')]
    public function signupAgentSuccess(): Response
    {
        return $this->render('security/signup_agent_success.html.twig');
    }

    #[Route('/signup/agent/validate-step', name: 'app_signup_agent_validate', methods: ['POST'])]
    public function validateAgentStep(Request $request, UtilisateurRepository $repo): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $step = (int) $request->request->get('step');
        $errors = [];

        if ($step === 1) {
            $email    = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm_password', '');
            $prenom   = trim($request->request->get('prenom', ''));
            $nom      = trim($request->request->get('nom', ''));
            $tel      = trim($request->request->get('telephone', ''));

            if (!$prenom) $errors['prenom'] = 'First name is required.';
            if (!$nom)    $errors['nom']    = 'Last name is required.';
            if (!$tel)    $errors['telephone'] = 'Phone number is required.';
            if (!$email)  $errors['email']  = 'Email is required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
            elseif ($repo->findOneBy(['email' => $email])) $errors['email'] = 'This email is already registered.';
            if (!$password) $errors['password'] = 'Password is required.';
            elseif (strlen($password) < 6) $errors['password'] = 'Minimum 6 characters.';
            if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($step === 2) {
            $fields = ['nom_agence' => 'Agency name', 'nom_legal_agence' => 'Legal name',
                       'email_agence' => 'Agency email', 'telephone_agence' => 'Agency phone',
                       'pays_agence' => 'Country', 'ville_agence' => 'City', 'adresse_agence' => 'Address'];
            foreach ($fields as $field => $label) {
                if (!trim($request->request->get($field, '')))
                    $errors[$field] = "$label is required.";
            }
            $emailAgence = trim($request->request->get('email_agence', ''));
            if ($emailAgence && !filter_var($emailAgence, FILTER_VALIDATE_EMAIL))
                $errors['email_agence'] = 'Invalid agency email.';
        }

        if ($step === 3) {
            $fields = ['numero_registre_commerce' => 'Trade register', 'numero_fiscal' => 'Tax number',
                       'numero_licence_agence' => 'Licence number', 'date_enregistrement' => 'Registration date'];
            foreach ($fields as $field => $label) {
                if (!trim($request->request->get($field, '')))
                    $errors[$field] = "$label is required.";
            }
        }

        return $this->json(['valid' => empty($errors), 'errors' => $errors]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UtilisateurRepository $repo, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $error = null;
        $sent  = false;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $user  = $repo->findOneBy(['email' => $email]);

            if (!$user) {
                $error = 'No account found with that email.';
            } else {
                $code   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiry = (new \DateTime('+15 minutes'))->format('Y-m-d H:i:s');
                $now    = (new \DateTime())->format('Y-m-d H:i:s');
                $em->getConnection()->executeStatement(
                    'INSERT INTO verification_codes (email, code, expiration_time, created_at, is_used) VALUES (?, ?, ?, ?, 0)',
                    [$email, $code, $expiry, $now]
                );
                $mailer->send((new Email())
                    ->from('saadaouilouay16@gmail.com')->to($email)
                    ->subject('Explora — Reset your password')
                    ->html('<div style="font-family:Inter,sans-serif;max-width:480px;margin:auto;padding:32px;background:#f8fafc;border-radius:12px;">
                        <h2 style="color:#1e5faf;margin-bottom:8px;">Password Reset</h2>
                        <p style="color:#64748b;margin-bottom:24px;">Use the code below to reset your password. Expires in <strong>15 minutes</strong>.</p>
                        <div style="background:#1e5faf;color:white;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:10px;">'.$code.'</div>
                        <p style="color:#94a3b8;font-size:12px;margin-top:24px;">If you did not request this, ignore this email.</p>
                    </div>')
                );
                $request->getSession()->set('reset_email', $email);
                return $this->redirectToRoute('app_reset_verify');
            }
        }

        return $this->render('security/forgot_password.html.twig', ['error' => $error]);
    }

    #[Route('/reset-password/verify', name: 'app_reset_verify', methods: ['GET', 'POST'])]
    public function resetVerify(Request $request, EntityManagerInterface $em): Response
    {
        $email = $request->getSession()->get('reset_email');
        if (!$email) return $this->redirectToRoute('app_forgot_password');

        $error = null;

        if ($request->isMethod('POST')) {
            $entered = trim($request->request->get('code', ''));
            $row = $em->getConnection()->fetchAssociative(
                'SELECT * FROM verification_codes WHERE email = ? AND (is_used = 0 OR is_used IS NULL) AND expiration_time > ? ORDER BY id DESC LIMIT 1',
                [$email, (new \DateTime())->format('Y-m-d H:i:s')]
            );

            if (!$row || trim($row['code']) !== $entered) {
                $error = 'Invalid or expired code.';
            } else {
                $em->getConnection()->executeStatement('UPDATE verification_codes SET is_used = 1 WHERE id = ?', [$row['id']]);
                $request->getSession()->set('reset_verified', true);
                return $this->redirectToRoute('app_reset_new_password');
            }
        }

        return $this->render('security/reset_verify.html.twig', ['email' => $email, 'error' => $error]);
    }

    #[Route('/reset-password/new', name: 'app_reset_new_password', methods: ['GET', 'POST'])]
    public function resetNewPassword(Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $email    = $request->getSession()->get('reset_email');
        $verified = $request->getSession()->get('reset_verified');
        if (!$email || !$verified) return $this->redirectToRoute('app_forgot_password');

        $error = null;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm', '');

            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $user = $repo->findOneBy(['email' => $email]);
                if ($user) {
                    $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
                    $em->flush();
                }
                $request->getSession()->remove('reset_email');
                $request->getSession()->remove('reset_verified');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_new_password.html.twig', ['error' => $error]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('app_login');
    }
}
