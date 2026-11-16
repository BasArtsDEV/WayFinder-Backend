<?php

namespace App\EventListener;

use App\Event\UserConfirmationEmailNotReceivedEvent;
use App\Event\UserRegisteredEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class EmailVerifierListener
{
    public function onUserRegisteredEvent(UserRegisteredEvent $event): void
    {
        // ...
    }

    public function onUserConfirmationEmailNotReceivedEvent(UserConfirmationEmailNotReceivedEvent $event): void
    {
        // ...
    }
}
