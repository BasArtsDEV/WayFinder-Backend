<?php

namespace App\Security\Voter;

use App\Entity\TreasureHunt;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, TreasureHunt>
 */
final class TreasureHuntVoter extends Voter
{
    public const string ADD = 'TREASURE_HUNT_ADD';
    public const string EDIT = 'TREASURE_HUNT_EDIT';
    public const string DELETE = 'TREASURE_HUNT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return match ($attribute) {
            self::ADD => null == $subject,
            self::EDIT, self::DELETE => $subject instanceof TreasureHunt,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (null == $user) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        $userTeams = $user->getTeams();

        return match ($attribute) {
            self::ADD => !$userTeams->isEmpty(),
            self::EDIT, self::DELETE => $userTeams->contains($subject->getTeam()),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }
}
