<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return ['app_notifications' => []];
        }

        return [
            'app_notifications' => $this->notificationRepository->findByAny($user),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->decodeJson(...)),
        ];
    }

    public function decodeJson(?string $string): array
    {
        if (empty($string)) {
            return [];
        }

        return json_decode($string, true) ?? [];
    }
}
