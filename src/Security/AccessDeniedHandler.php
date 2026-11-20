<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private const array ROUTE_FALLBACKS = [
        'app_treasure_hunt_edit' => ['route_redirection' => 'app_treasure_hunt_show'],
        'app_treasure_hunt_delete' => ['route_redirection' => 'app_treasure_hunt_show'],

        'app_riddle_new' => ['route_redirection' => 'app_treasure_hunt_show'],
        'app_riddle_edit' => ['route_redirection' => 'app_treasure_hunt_show'],
        'app_riddle_delete' => ['route_redirection' => 'app_treasure_hunt_show'],

        'app_team_edit' => ['route_redirection' => 'app_team_show'],
        'app_team_delete' => ['route_redirection' => 'app_team_show'],
        'app_team_invite' => ['route_redirection' => 'app_team_show'],
        'app_team_promote' => ['route_redirection' => 'app_team_show'],
        'app_team_exclude' => ['route_redirection' => 'app_team_show'],
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $currentRoute = $request->attributes->get('_route');
        $routeParameters = $request->attributes->get('_route_params');

        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('danger', 'Vous n\'êtes pas autorisé à accéder à cette page.');

        return new RedirectResponse($this->urlGenerator->generate(self::ROUTE_FALLBACKS[$currentRoute]['route_redirection'], $routeParameters));
    }
}
