<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager,SessionInterface $session): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $userToMakeWait = new User();
            $userToMakeWait->setEmail($user->getEmail())
                ->setPassword($user->getPassword())
                ->setRoles($user->getRoles())
                ->setIsPaid(0);
            $entityManager->persist($userToMakeWait);
            $entityManager->flush();
            $session->set('pending_user', [
                'email' => $user->getEmail(),
                'password' => $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()),
            ]);

            // $entityManager->persist($user);
            // $entityManager->flush();

            // do anything else you need here, like send an email
            return $this->redirectToRoute('app_pricing');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
