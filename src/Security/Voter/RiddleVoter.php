<?php

namespace App\Security\Voter;

use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Riddle|TreasureHunt>
 */
final class RiddleVoter extends Voter
{
    public const string ADD = 'RIDDLE_ADD';
    public const string EDIT = 'RIDDLE_EDIT';
    public const string DELETE = 'RIDDLE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::ADD => $subject instanceof TreasureHunt,
            self::EDIT, self::DELETE => $subject instanceof Riddle,
            default => false,
        };
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
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
        $treasureHunt = $subject instanceof Riddle ? $subject->getTreasureHunt() : $subject;

        $team = $treasureHunt?->getTeam();
        if (null == $team) {
            return false;
        }

        return match ($attribute) {
            self::ADD, self::EDIT, self::DELETE => $user->getTeams()->contains($team),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }
}
