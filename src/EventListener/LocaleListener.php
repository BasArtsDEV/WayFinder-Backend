<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Selects the locale of the request: language cookie, then Accept-Language header, then default locale.
 */
final readonly class LocaleListener
{
    public const string COOKIE_NAME = 'app_locale';

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%app.enabled_locales%')] private array $enabledLocales,
        #[Autowire('%kernel.default_locale%')] private string $defaultLocale,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $this->fromCookie($request) ?? $this->fromAcceptLanguage($request) ?? $this->defaultLocale;

        $request->setLocale($locale);
    }

    private function fromCookie(Request $request): ?string
    {
        $value = $request->cookies->get(self::COOKIE_NAME);

        return \is_string($value) && \in_array($value, $this->enabledLocales, true) ? $value : null;
    }

    private function fromAcceptLanguage(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            foreach ($this->enabledLocales as $locale) {
                if (0 === strcasecmp($language, $locale)) {
                    return $locale;
                }
            }

            $primary = strtolower(explode('_', $language)[0]);
            foreach ($this->enabledLocales as $locale) {
                if (strtolower(explode('_', $locale)[0]) === $primary) {
                    return $locale;
                }
            }
        }

        return null;
    }
}
