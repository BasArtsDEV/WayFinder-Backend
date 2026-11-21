<?php

namespace App\Security;

use App\Entity\Riddle;
use App\Entity\Team;
use App\Entity\TreasureHunt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private const string DEFAULT_ROUTE = 'app_home';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        [$route, $parameter] = $this->handleRoutesRedirection($accessDeniedException->getSubject());

        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('danger', 'Vous n\'êtes pas autorisé à accéder à cette page.');

        return new RedirectResponse($this->urlGenerator->generate($route, $parameter));
    }

    /**
     * @return list<string | array<string, int|null>>
     */
    private function handleRoutesRedirection(mixed $subject): array
    {
        return match (true) {
            $subject instanceof Riddle => [
                'app_treasure_hunt_show',
                ['id' => $subject->getTreasureHunt()->getId()],
            ],
            $subject instanceof TreasureHunt => [
                'app_treasure_hunt_show',
                ['id' => $subject->getId()],
            ],
            $subject instanceof Team => [
                'app_team_show',
                ['id' => $subject->getId()],
            ],
            default => [self::DEFAULT_ROUTE, []],
        };
    }
}
