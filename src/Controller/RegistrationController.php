<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\UserConfirmationEmailNotReceivedEvent;
use App\Event\UserRegisteredEvent;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private readonly EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('password')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $eventDispatcher->dispatch(new UserRegisteredEvent($user));

            $this->addFlash('success', 'Votre compte a été créé avec succès. Un email de vérification vous a été envoyé.');

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');
        if (null === $id) {
            return $this->redirectToRoute('app_validate_user_mail');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            return $this->redirectToRoute('app_validate_user_mail');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_validate_user_mail');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse e-mail a bien été vérifiée.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/verify/email/resend', name: 'app_validate_user_mail')]
    public function validateUserMail(Request $request, Security $security, EventDispatcherInterface $eventDispatcher): Response
    {
        /** @var User $user */
        $user = $security->getUser();
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if (null == $user || $user->isVerified()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $eventDispatcher->dispatch(new UserConfirmationEmailNotReceivedEvent($user));
            $this->addFlash('success', 'Un nouvel e-mail de confirmation vous a été envoyé.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/validate_email.html.twig', [
            'validationForm' => $form->createView(),
        ]);
    }
}
