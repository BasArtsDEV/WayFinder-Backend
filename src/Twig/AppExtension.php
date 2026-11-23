<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_notifications_count', $this->getNotificationsCount(...)),
        ];
    }

    public function getNotificationsCount(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->notificationRepository->countByUser($user);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->decodeJson(...)),
            new TwigFilter('duration', $this->formatDuration(...)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeJson(?string $string): array
    {
        if (empty($string)) {
            return [];
        }

        return json_decode($string, true) ?? [];
    }

    public function formatDuration(?int $seconds): string
    {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%d h %02d min', $hours, $minutes);
        }

        return sprintf('%d min %02d s', $minutes, $seconds % 60);
    }
}
