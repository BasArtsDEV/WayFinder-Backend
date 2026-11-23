<?php

namespace App\Tests\Twig;

use App\Service\UserTimezoneProvider;
use App\Twig\Extension\DateFormatterExtension;
use App\Twig\Runtime\DateFormatterExtensionRuntime;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

class DateFormatterExtensionTest extends IntegrationTestCase
{
    public function getExtensions(): array
    {
        return [
            new DateFormatterExtension(),
        ];
    }

    public function getRuntimeLoaders(): array
    {
        return [
            new FactoryRuntimeLoader([
                DateFormatterExtensionRuntime::class => function () {
                    $translator = new Translator('fr_FR');
                    $translator->addLoader('yml', new YamlFileLoader());
                    $translator->addResource('yml', __DIR__.'/../../translations/fr-fr.yml', 'fr_FR', 'messages+intl-icu');

                    return new DateFormatterExtensionRuntime(
                        new MockClock(new \DateTimeImmutable('2023-01-01 20:12:00')),
                        $translator,
                        new UserTimezoneProvider(new TokenStorage()),
                    );
                },
            ]),
        ];
    }

    public static function getFixturesDirectory(): string
    {
        return __DIR__.'/Fixtures/';
    }
}
