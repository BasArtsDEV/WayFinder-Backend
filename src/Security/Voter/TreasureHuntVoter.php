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
    public const string EDIT = 'TREASURE_HUNT_EDIT';
    public const string DELETE = 'TREASURE_HUNT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof TreasureHunt;
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
        $team = $subject->getTeam();

        return match ($attribute) {
            self::EDIT, self::DELETE => $user->getTeams()->contains($team),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }
}
