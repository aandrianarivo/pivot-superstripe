<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\AppAuthAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator as AuthLoginAuthenticator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\JsonResponse;

use function PHPUnit\Framework\throwException;

#[IsGranted('PUBLIC_ACCESS')]
final class SubscriptionController extends AbstractController
{ 
    #[Route('/subscription', name: 'app_subscription')]
    public function subscribe(SessionInterface $session): Response
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    
        // Vérifier si un utilisateur en attente est en session
        $pendingUser = $session->get('pending_user');
        if (!$pendingUser || !isset($pendingUser['email'])) {
            return $this->redirectToRoute('app_register');
        }
    
        try {
            // ✅ OPTIONNEL : Créer un client Stripe (si tu veux l'associer à l'achat)
            $customer = \Stripe\Customer::create([
                'email' => $pendingUser['email'],
            ]);
            // dd($customer);
            
            $stripeSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => "price_1Qyq5RR9p9inY5nCUj6lYUgF", // L'ID du tarif associé à votre produit
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'customer' => $customer->id, // Associe l'utilisateur à un client Stripe
            ]);
            // dd($stripeSession);
    
            return new RedirectResponse($stripeSession->url, 303);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            dump($e->getMessage());
            throw $e;
        }
    }
    
    

    #[Route('/subscription/success', name: 'app_subscription_success')]
    public function success(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthAuthenticator  $authenticator,
        Request $request,
        TokenStorageInterface $tokenStorage
    ): Response {
        $pendingUser = $session->get('pending_user');
    
        if (!$pendingUser) {
            return $this->redirectToRoute('app_register');
        }
    
        // Vérifier si l'utilisateur existe déjà (précaution)
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $pendingUser['email']]);
        if ($existingUser) {
            return $this->redirectToRoute('app_login');
        }
    
        // Créer et enregistrer l'utilisateur après paiement
        $user = new User();
        $user->setEmail($pendingUser['email']);
        $user->setPassword($pendingUser['password']);
        $user->setRoles(['ROLE_USER']); // L'utilisateur devient actif après le paiement
    
        $entityManager->persist($user);
        $entityManager->flush();
    
        $session->remove('pending_user');
    
        // ✅ Authentification automatique après paiement
        return $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
    }

    #[Route('/test-redirection', name: 'app_test_redirect')]
    public function testRedirect(SessionInterface $session): Response
    {
        if (!$session->isStarted()) {
            $session->start();
        }
    
        return new RedirectResponse('https://www.google.com', 303);
    }

    // #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    // public function stripeWebhook(
    //     Request $request,
    //     EntityManagerInterface $entityManager,
    //     SessionInterface $session,
    //     LoggerInterface $logger
    // ): Response {
    //     \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    //     $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
    
    //     $payload = $request->getContent();
    //     $sig_header = $request->headers->get('stripe-signature');
    
    //     $logger->info("🚀 Webhook Stripe reçu !");
    //     $logger->info("Payload reçu : " . $payload);
    
    //     try {
    //         $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    //         $logger->info("✅ Événement Stripe validé : " . $event->type);
    //     } catch (\Stripe\Exception\SignatureVerificationException $e) {
    //         $logger->error("⚠️ Erreur de signature Stripe : " . $e->getMessage());
    //         return new Response('⚠️ Signature invalide', 400);
    //     }
    
    //     // Vérifier si le paiement a bien été effectué
    //     if ($event->type === 'checkout.session.completed') {
    //         sleep(15); // Attendre 15 secondes pour simuler un traitement long
    //         $this->addFlash('success', 'Le traitement du paiement va commencer');
    //         $sessionStripe = $event->data->object;
    //         $customerEmail = $sessionStripe->customer_details->email;


    //         // file_put_contents('stripe.log.completed',$event);
    //         // die();
    //         // $logger->error("⚠️stripe.log " .$sessionStripe);
    //         // dd($sessionStripe); // Arrêter tout traitement et afficher le contenu de stripe.log            $logger->info("🎯 Paiement validé pour session : " . $sessionStripe->id);
    
    //         if (!$customerEmail) {
    //             $logger->error("⚠️ Erreur : `customer_email` est vide dans Stripe.");
    //             return new Response('⚠️ Erreur : Pas d’email client dans Stripe', 400);
    //         }
    
    //         $logger->info("📧 Email du client récupéré : " . $customerEmail);
    
    //         // 🔹 Récupérer les données de l'utilisateur en attente de paiement depuis la session Symfony
    //         $pendingUser = $session->get('pending_user');
    
    //         if (!$pendingUser) {
    //             $logger->error("⚠️ Aucun utilisateur en attente trouvé en session.");
    //             return new Response('⚠️ Aucun utilisateur en attente trouvé', 400);
    //         }
    
    //         if ($pendingUser['email'] !== $customerEmail) {
    //             $logger->error("⚠️ L'email Stripe ne correspond pas à celui en session.");
    //             return new Response('⚠️ L’email du client ne correspond pas', 400);
    //         }
    
    //         // 🔹 Vérifier si un utilisateur avec cet email existe déjà
    //         $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $customerEmail]);
    
    //         if (!$existingUser) {
    //             $logger->info("🆕 Création d’un nouvel utilisateur : " . $customerEmail);
    //             $user = new User();
    //             $user->setEmail($customerEmail);
    //             $user->setPassword($pendingUser['password']); // 🔑 On utilise le mot de passe déjà hashé
    //             $user->setRoles(['ROLE_USER']);
    
    //             $entityManager->persist($user);
    //             $entityManager->flush();
    
    //             // ✅ Suppression des données temporaires en session après l'inscription réussie
    //             $session->remove('pending_user');
    //             $logger->info("✅ Utilisateur enregistré avec succès : " . $customerEmail);
    //         } else {
    //             $logger->info("ℹ️ L’utilisateur existe déjà : " . $customerEmail);
    //         }
    //     }
    
    //     return new Response('✅ Webhook traité', 200);
    // }

    #[Route('/pricing', name: 'app_pricing')]
        public function pricing(SessionInterface $session,): Response
    {
        $pendingUser = $session->get('pending_user');
        return $this->render('subscription/pricing.html.twig', [
            'email' => $pendingUser['email'],
        ]);
    }

    #[Route('/sub/pricing', name: 'app_sub_pricing')]
    public function subscriptionPricing(SessionInterface $session,): Response
    {
        // $pendingUser = $session->get('pending_user');
        return $this->render('subscription/sub_pricing.html.twig', [
            'plans' => [
                [
                    'name' => 'Standard',
                    'price' => '5€ / mois',
                    'price_id' => 'price_1Qyq5RR9p9inY5nCUj6lYUgF',
                ],
                [
                    'name' => 'Premium',
                    'price' => '20€ / mois',
                    'price_id' => 'price_1QyqcPR9p9inY5nCHWTOpVWf',
                ]
            ]
        ]);
        
        
    }
    

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(Request $request,SessionInterface $session)
    {
        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $priceId = $request->get('priceId');
            $checkoutSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId, 
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->generateUrl('app_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            // dd($checkoutSession);
            // return new RedirectResponse($checkoutSession->url);
            // $this->addFlash('success', 'Votre paiement a été validé avec succès !');
            // header("Location: " . $checkoutSession->url);
            return new RedirectResponse($checkoutSession->url, 303);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception("La clé API Stripe est invalide ou non définie.");
        }

    }

    #[Route('/sub/success', name: 'app_success')]
    public function successCheckout(): Response
    {
        // $this->addFlash('success', 'Votre paiement a été validé avec succès !');
        return $this->render('subscription/success.html.twig');
    }

    
    

    // #[Route('/stripe/webhook', name: 'stripe_webhook')]
        // public function stripeWebhook(
        //     Request $request,
        //     EntityManagerInterface $entityManager
        // ): Response {
        //     dump("🚀 Webhook Stripe reçu !");
        //     die(); // Arrête l'exécution pour voir si cette ligne est atteinte.

        //     return new Response('✅ Webhook traité', 200);
        // }


    #[Route('/choose-subscription', name: 'choose_subscription')]
    public function chooseSubscription(): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $prices = Price::all(['limit' => 30]);
        // dd($prices);

        return $this->render('subscription/choose.html.twig', [
            'prices' => $prices->data
        ]);
        
    }
    #[Route('/payment-checkout/{priceId}', name: 'payment_checkout')]
    public function paymentCheckout(string $priceId,SessionInterface $session)
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        // Vérifier si l'utilisateur temporaire existe
        $userData = $session->get('pending_user');
        if (!$userData) {
            return $this->redirectToRoute('app_register');
        }
            // Création de la session Stripe Checkout
        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer_email' => $userData['email'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $this->generateUrl('app_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('choose_subscription', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // return $this->redirect($session->url);
        return new RedirectResponse($session->url);

    }
    // #[Route('/webhook/stpe', name: 'appstripe_webhook', methods: ['POST'])]
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWk(
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
    
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

    
            $logger->info('Webhook reçu, type : ' . $event->type);
        
            if ($event->type === 'checkout.session.completed') {
                $logger->warning('Stripe : Paiement confirmé ✅');
        
                $sessionData = $event->data->object;
                $email = $sessionData->customer_email;
        
                // 🔍 Récupérer l'utilisateur en base de données
                $existingUser = $entityManager->getRepository(User::class)->findOneByEmailAndIsPaid($email,false);
        
                if ($existingUser) {
                    $logger->error('Utilisateur trouvé : ' . $email);
        
                    // ✅ Mettre à jour le statut de l’utilisateur après paiement
                    $existingUser->setIsPaid(true); // Assurez-vous d’avoir un champ `isPaid` dans l’entité User
                    $entityManager->persist($existingUser);
                    $entityManager->flush();
        
                    $logger->error('Utilisateur mis à jour et confirmé dans la base de données ! 🎉');
        
                    return new JsonResponse(['status' => 'User updated successfully']);
                } else {
                    $logger->error('Aucun utilisateur errortrouvé avec cet email: ' . $email);
                    return new JsonResponse(['error' => 'User not found'], 404);
                }
            }
            return new JsonResponse(['status' => 'Webhook received']);
        } catch (\Exception $e) {
            $logger->error('Webhook signature invalid: ' . $e->getMessage());
            throwException($e);
            return new JsonResponse(['error' => 'Webhook signature invalid'], 400);
        }
    }
    

}