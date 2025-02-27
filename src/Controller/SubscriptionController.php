<?php

namespace App\Controller;

use App\Entity\User;
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
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator as AuthLoginAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;


final class SubscriptionController extends AbstractController
{
    #[Route('/subscription', name: 'app_subscription')]
    public function subscribe(SessionInterface $session)
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        if (!$session->has('pending_user')) {
            return $this->redirectToRoute('app_register');
        }
        $stripeSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Abonnement'],
                    'unit_amount' => 2000,
                    'recurring' => ['interval' => 'month'],
                ],
                'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        dd($stripeSession);

        try {
            $stripeSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => 'Abonnement'],
                        'unit_amount' => 2000,
                        'recurring' => ['interval' => 'month'],
                    ],
                    'quantity' => 1,
                    ]],
                    'mode' => 'subscription',
                    'success_url' => $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url' => $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
                
            dd($stripeSession);
            return $this->redirect($stripeSession->url);
        } catch (ApiErrorException $e) {
            // Ajoute un log ou affiche l’erreur
            dump($e->getMessage());
            throw $e; // Ou redirige vers une page d’erreur
        }
    }

    #[Route('/subscription/success', name: 'app_subscription_success')]
    public function success(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AuthLoginAuthenticator $authenticator,
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
    #[Route('/xyg', name: 'app_xyg')]
    public function xyg(): Response
    {
        return $this->json("Hello");
    }
}
