<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides the timezone dates must be displayed in: the one of the connected user,
 * or the PHP default timezone for anonymous visitors.
 */
final readonly class UserTimezoneProvider
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getTimezone(): \DateTimeZone
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if ($user instanceof User) {
            try {
                return new \DateTimeZone($user->getTimezone());
            } catch (\Exception) {
                // Invalid identifier stored: use the default timezone
            }
        }

        return new \DateTimeZone(date_default_timezone_get());
    }
}
