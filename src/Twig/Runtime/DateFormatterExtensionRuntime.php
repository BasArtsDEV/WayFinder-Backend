<?php

namespace App\Twig\Runtime;

use App\Service\UserTimezoneProvider;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class DateFormatterExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ClockInterface $clock,
        private TranslatorInterface $translator,
        private UserTimezoneProvider $timezoneProvider,
    ) {
    }

    /**
     * Formats a date relatively to now ("5 minutes ago", "Thursday at 00:59", ...),
     * in the language of the request and in the timezone of the connected user.
     *
     * @param string|null $timezone timezone identifier forced for this call, the user's one by default
     * @param string|null $locale   locale forced for this call, the request's one by default
     */
    public function format(\DateTimeInterface $value, ?string $timezone = null, ?string $locale = null): string
    {
        $timeZone = $this->resolveTimezone($timezone);
        $locale ??= $this->translator instanceof LocaleAwareInterface ? $this->translator->getLocale() : \Locale::getDefault();

        // Both dates are expressed in the same timezone so that days and hours are comparable
        $now = \DateTimeImmutable::createFromInterface($this->clock->now())->setTimezone($timeZone);
        $value = \DateTimeImmutable::createFromInterface($value)->setTimezone($timeZone);
        $diff = abs($now->getTimestamp() - $value->getTimestamp());

        if ($diff < 300) {
            return $this->translator->trans('date.relative.now', [], null, $locale);
        }

        if ($diff < 3600) {
            return $this->translator->trans('date.relative.minutes', ['count' => (int) round($diff / 60)], null, $locale);
        }

        if ($diff < 86400) {
            return $this->translator->trans('date.relative.hours', ['count' => (int) floor($diff / 3600)], null, $locale);
        }

        // Less than 6 days
        if ($diff < 6 * 86400) {
            $days = (int) round($diff / 86400);

            if (1 === $days && $now->format('d') !== $value->format('d')) {
                return $this->translator->trans('date.relative.hours', ['count' => 23], null, $locale);
            }

            return $this->translator->trans('date.weekday', [
                'day' => $this->formatPattern($value, 'EEEE', $locale),
                'time' => $this->formatTime($value, $locale),
            ], null, $locale);
        }

        return $this->translator->trans('date.absolute', [
            'date' => $this->formatPattern($value, $this->translator->trans('date.pattern.date', [], null, $locale), $locale),
            'time' => $this->formatTime($value, $locale),
        ], null, $locale);
    }

    private function formatTime(\DateTimeImmutable $value, string $locale): string
    {
        return $this->formatPattern($value, $this->translator->trans('date.pattern.time', [], null, $locale), $locale);
    }

    private function formatPattern(\DateTimeImmutable $value, string $pattern, string $locale): string
    {
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $value->getTimezone(), \IntlDateFormatter::GREGORIAN, $pattern);

        return (string) $formatter->format($value);
    }

    private function resolveTimezone(?string $timezone): \DateTimeZone
    {
        if (null !== $timezone) {
            try {
                return new \DateTimeZone($timezone);
            } catch (\Exception) {
                // Invalid identifier: fall back to the user's timezone
            }
        }

        return $this->timezoneProvider->getTimezone();
    }
}
