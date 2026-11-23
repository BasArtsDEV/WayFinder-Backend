<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Languages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleExtension extends AbstractExtension
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%app.enabled_locales%')] private readonly array $enabledLocales,
        #[Autowire('%kernel.default_locale%')] private readonly string $defaultLocale,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('available_locales', $this->getAvailableLocales(...)),
            new TwigFunction('html_lang', $this->getHtmlLang(...)),
        ];
    }

    /**
     * @return list<array{code: string, short: string, name: string, active: bool}>
     */
    public function getAvailableLocales(): array
    {
        $current = $this->getCurrentLocale();
        $locales = [];

        foreach ($this->enabledLocales as $locale) {
            $language = explode('_', $locale)[0];

            // Each language is named in its own language
            $name = Languages::exists($language) ? Languages::getName($language, $language) : $locale;

            $locales[] = [
                'code' => $locale,
                'short' => strtoupper($language),
                'name' => mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1),
                'active' => $locale === $current,
            ];
        }

        return $locales;
    }

    /**
     * Current locale as a BCP 47 tag, for the "lang" attribute of the page ("fr_FR" => "fr-FR").
     */
    public function getHtmlLang(): string
    {
        return str_replace('_', '-', $this->getCurrentLocale());
    }

    private function getCurrentLocale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;
    }
}
