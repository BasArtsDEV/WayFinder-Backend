<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Service\UserTimezoneProvider;
use App\Twig\Runtime\DateFormatterExtensionRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class DateFormatterTimezoneTest extends TestCase
{
    private function createRuntime(?string $userTimezone, string $locale = 'fr_FR'): DateFormatterExtensionRuntime
    {
        $translator = new Translator($locale);
        $translator->addLoader('yml', new YamlFileLoader());
        foreach (['fr_FR' => 'fr-fr', 'en_US' => 'en-us'] as $code => $file) {
            $translator->addResource('yml', __DIR__.'/../../translations/'.$file.'.yml', $code, 'messages+intl-icu');
        }

        $tokenStorage = new TokenStorage();
        if (null !== $userTimezone) {
            $user = new User();
            $user->setTimezone($userTimezone);
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }

        return new DateFormatterExtensionRuntime(
            new MockClock(new \DateTimeImmutable('2023-03-01 12:00:00', new \DateTimeZone('UTC'))),
            $translator,
            new UserTimezoneProvider($tokenStorage),
        );
    }

    public function testAbsoluteDateIsConvertedToUserTimezone(): void
    {
        $date = new \DateTimeImmutable('2023-02-15 13:42:00', new \DateTimeZone('UTC'));

        self::assertSame('le 15/02/2023 à 14:42', $this->createRuntime('Europe/Paris')->format($date));
        self::assertSame('le 15/02/2023 à 22:42', $this->createRuntime('Asia/Tokyo')->format($date));
        self::assertSame('le 15/02/2023 à 08:42', $this->createRuntime('America/New_York')->format($date));
    }

    public function testDayChangesWithTimezone(): void
    {
        $date = new \DateTimeImmutable('2023-02-15 23:30:00', new \DateTimeZone('UTC'));

        self::assertSame('le 16/02/2023 à 08:30', $this->createRuntime('Asia/Tokyo')->format($date));
    }

    public function testEnglishFormat(): void
    {
        $date = new \DateTimeImmutable('2023-02-15 13:42:00', new \DateTimeZone('UTC'));

        self::assertSame('on 02/15/2023 at 1:42 PM', $this->createRuntime('UTC', 'en_US')->format($date));
    }

    public function testRelativeDatesInEnglish(): void
    {
        $runtime = $this->createRuntime('UTC', 'en_US');

        self::assertSame('Just now', $runtime->format(new \DateTimeImmutable('2023-03-01 12:01:00', new \DateTimeZone('UTC'))));
        self::assertSame('1 hour ago', $runtime->format(new \DateTimeImmutable('2023-03-01 10:30:00', new \DateTimeZone('UTC'))));
        self::assertSame('5 hours ago', $runtime->format(new \DateTimeImmutable('2023-03-01 07:00:00', new \DateTimeZone('UTC'))));
        self::assertSame('Monday at 12:00', $runtime->format(new \DateTimeImmutable('2023-02-27 12:00:00', new \DateTimeZone('UTC'))));
    }

    public function testExplicitTimezoneOverridesUserTimezone(): void
    {
        $date = new \DateTimeImmutable('2023-02-15 13:42:00', new \DateTimeZone('UTC'));

        self::assertSame('le 15/02/2023 à 22:42', $this->createRuntime('Europe/Paris')->format($date, 'Asia/Tokyo'));
    }

    public function testInvalidTimezoneFallsBack(): void
    {
        $date = new \DateTimeImmutable('2023-02-15 13:42:00', new \DateTimeZone('UTC'));

        self::assertSame('le 15/02/2023 à 22:42', $this->createRuntime('Asia/Tokyo')->format($date, 'Not/AZone'));
    }
}
