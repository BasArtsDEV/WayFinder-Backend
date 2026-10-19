<?php

namespace App\Twig\Runtime;

use Symfony\Component\Clock\ClockInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class DateFormatterExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function doSomething(\DateTimeInterface $value): string
    {
        $now = $this->clock->now();
        $diff = abs($now->getTimestamp() - $value->getTimestamp());

        if ($diff < 300) {
            return "A l'instant";
        }

        if ($diff < 3600) {
            $minutes = (int) round($diff / 60);

            return sprintf('il y a %d minutes', $minutes);
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return sprintf('il y a %d heure%s', $hours, $hours > 1 ? 's' : '');
        }

        // Moins de 6 jours
        if ($diff < 6 * 86400) {
            $days = (int) round($diff / 86400);

            if (1 === $days && $now->format('d') !== $value->format('d')) {
                return 'il y a 23 heures';
            }

            $daysOfWeek = [
                'Monday' => 'lundi',
                'Tuesday' => 'mardi',
                'Wednesday' => 'mercredi',
                'Thursday' => 'jeudi',
                'Friday' => 'vendredi',
                'Saturday' => 'samedi',
                'Sunday' => 'dimanche',
            ];
            $dayName = $daysOfWeek[$value->format('l')] ?? $value->format('l');

            return sprintf('%s à %s', $dayName, $value->format('H:i'));
        }

        // Plus de 6 jours
        return sprintf('le %s à %s', $value->format('d/m/Y'), $value->format('H:i'));
    }
}
