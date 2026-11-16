<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\UserConfirmationEmailNotReceivedEvent;
use App\Event\UserRegisteredEvent;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class EmailVerifierListener
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    public function onUserRegisteredEvent(UserRegisteredEvent $event): void
    {
        $this->sendConfirmationEmail($event->getUser());
    }

    public function onUserConfirmationEmailNotReceivedEvent(UserConfirmationEmailNotReceivedEvent $event): void
    {
        $this->sendConfirmationEmail($event->getUser());
    }

    private function sendConfirmationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            new TemplatedEmail()
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
