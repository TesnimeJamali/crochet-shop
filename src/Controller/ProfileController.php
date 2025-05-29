<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    #[Route('/profileA', name: 'app_profileA')]
    #[IsGranted('ROLE_USER')] // Sécurise l'accès, uniquement pour les utilisateurs connectés
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté

        // Crée le formulaire en passant l'utilisateur actuel
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le mot de passe a été modifié, le hasher
            if ($form->get('plainPassword')->getData()) {
                $plainPassword = $form->get('plainPassword')->getData();
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('app_profile'); // Redirige vers la page de profil
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $form->createView(),
            'user' => $user, // Passe l'utilisateur à la vue pour afficher les infos
        ]);
    }
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function monProfil(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        return $this->render('profile/monProfil.html.twig', ["user"=>$user]);
    }

    #[Route('/profil/modifier', name: 'app_profile_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileTypeForm::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil est mis à jour.');
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

