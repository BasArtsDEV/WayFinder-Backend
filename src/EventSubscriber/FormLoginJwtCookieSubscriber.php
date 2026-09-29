<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class FormLoginJwtCookieSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthenticationSuccessHandler $authenticationSuccessHandler,
        private RequestStack $request,
    ) {
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        if ('Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator' !== $event->getAuthenticator()::class) {
            return;
        }

        $user = $event->getUser();
        $response = $event->getResponse();

        if ($response) {
            $session = $this->request->getSession();
            $session->getFlashBag()->add('success', 'Connexion réussie');

            $jwtResponse = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
            $cookies = $jwtResponse->headers->getCookies();

            foreach ($cookies as $cookie) {
                $response->headers->setCookie($cookie);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
        ];
    }
}
