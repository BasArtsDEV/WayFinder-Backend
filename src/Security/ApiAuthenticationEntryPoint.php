<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @throws HttpException
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $route = $request->attributes->get('_route');

        if (null != $route && str_starts_with($route, '_api_')) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED);
        }

        $session = $request->getSession();

        if (null != $session->getBag('flashes')) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $session->getBag('flashes');
            $flashBag->add('note', 'Vous devez vous authentifier pour accéder à cette page.');
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
