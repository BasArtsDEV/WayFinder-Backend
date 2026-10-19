<?php

namespace App\Tests\Twig;

use App\Twig\Extension\DateFormatterExtension;
use App\Twig\Runtime\DateFormatterExtensionRuntime;
use Symfony\Component\Clock\MockClock;
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
                    return new DateFormatterExtensionRuntime(
                        new MockClock(new \DateTimeImmutable('2023-01-01 20:12:00'))
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
