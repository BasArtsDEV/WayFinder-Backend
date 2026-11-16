<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ForceValidateMailListener
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener]
    public function onRequestEvent(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (null == $user || $user->isVerified()) {
            return;
        }

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        $allowedRoutes = [
            'app_validate_user_mail',
            'app_verify_email',
            'app_register',
            'app_logout',
            'app_login',
        ];

        if (in_array($currentRoute, $allowedRoutes, true)) {
            return;
        }

        $url = $this->urlGenerator->generate('app_verify_email');
        $event->setResponse(new RedirectResponse($url));
    }
}
