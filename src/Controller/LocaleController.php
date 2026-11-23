<?php

namespace App\Controller;

use App\EventListener\LocaleListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%app.enabled_locales%')] private readonly array $enabledLocales,
    ) {
    }

    #[Route('/locale/{locale}', name: 'app_locale_switch', requirements: ['locale' => '[a-z]{2,3}_[A-Z]{2}'], methods: ['GET'])]
    public function switch(string $locale, Request $request): RedirectResponse
    {
        if (!\in_array($locale, $this->enabledLocales, true)) {
            throw $this->createNotFoundException();
        }

        $response = new RedirectResponse($this->getRedirectUrl($request));
        $response->headers->setCookie(
            Cookie::create(LocaleListener::COOKIE_NAME, $locale, new \DateTimeImmutable('+1 year'))
        );

        return $response;
    }

    /**
     * Goes back to the previous page, only if it belongs to this site (no open redirect).
     */
    private function getRedirectUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');

        if (null !== $referer) {
            $host = parse_url($referer, \PHP_URL_HOST);

            if (\is_string($host) && $host === $request->getHost()) {
                return $referer;
            }
        }

        return $this->generateUrl('app_home');
    }
}
