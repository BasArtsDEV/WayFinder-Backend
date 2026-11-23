<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * Registers every "translations/<language>-<region>.yml" file (fr-fr.yml, en-us.yml, ...)
 * as the translation catalogue of the matching locale (fr_FR, en_US, ...).
 *
 * Symfony only discovers files named "<domain>.<locale>.<format>" by itself,
 * so this pass registers the one-file-per-language layout manually.
 * Dropping a new file (e.g. es-es.yml) in translations/ is enough to add a language.
 */
final class LocaleTranslationFilesPass implements CompilerPassInterface
{
    public const string FILE_PATTERN = '/^[a-z]{2,3}-[a-z]{2}\.yml$/i';

    public function process(ContainerBuilder $container): void
    {
        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');
        $directory = $projectDir.'/translations';

        if (!is_dir($directory) || !$container->hasDefinition('translator.default')) {
            $container->setParameter('app.enabled_locales', [(string) $container->getParameter('kernel.default_locale')]);

            return;
        }

        $translator = $container->getDefinition('translator.default');
        $locales = [];

        $files = Finder::create()->files()->in($directory)->depth(0)->name(self::FILE_PATTERN)->sortByName();

        foreach ($files as $file) {
            [$language, $region] = explode('-', $file->getBasename('.yml'));
            $locale = strtolower($language).'_'.strtoupper($region);
            $locales[] = $locale;

            foreach (['messages', 'validators'] as $domain) {
                $translator->addMethodCall('addResource', ['yml', $file->getPathname(), $locale, $domain.'+intl-icu']);
            }
        }

        $container->addResource(new GlobResource($directory, '/*.yml', false));

        $default = (string) $container->getParameter('kernel.default_locale');
        // Fallback of the language detection
        usort($locales, static fn (string $a, string $b): int => [$default !== $a, $a] <=> [$default !== $b, $b]);

        $container->setParameter('app.enabled_locales', $locales ?: [$default]);
    }
}
