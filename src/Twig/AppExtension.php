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
}
