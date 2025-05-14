<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Psr\Log\LoggerInterface;


class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager , LoginFormAuthenticator $formAuthenticator,LoggerInterface $logger): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            try {
                $entityManager->flush();
                $logger->info('Utilisateur enregistré avec succès : ' . $user->getEmail()); // Log succès
            } catch (\Exception $e) {
                $logger->error('Erreur lors de l\'enregistrement de l\'utilisateur : ' . $e->getMessage()); // Log erreur
                $this->addFlash('error', 'Une erreur s\'est produite lors de l\'enregistrement. Veuillez réessayer.');
                return $this->redirectToRoute('app_register'); // Redirect pour réessayer
            }


            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@crochet-shop.local', 'Crochet-shop'))
                    ->to((string) $user->getUserIdentifier())
                    ->subject('Priére de confirmer votre Email ')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email

            // Connecter l'utilisateur et le rediriger après l'inscription
            return $security->login($user, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),


        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository,Security $security, LoginFormAuthenticator $form): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse email a été vérifiée.');

        // Connecter l'utilisateur après la vérification de l'email
        $security->login($user, $form, 'main');

        // Rediriger l'utilisateur vers une page appropriée après la vérification et la connexion
        return $this->redirectToRoute('app_welcome'); // Remplace 'app_home' par la route de ta page d'accueil ou de profil
    }}