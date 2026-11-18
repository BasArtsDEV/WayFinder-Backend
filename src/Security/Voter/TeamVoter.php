<?php

namespace App\Security\Voter;

use App\Entity\Team;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, Team>
 */
final class TeamVoter extends Voter
{
    /** Regular users */
    public const string LEAVE = 'TEAM_LEAVE';
    public const string REQUEST_JOIN = 'TEAM_REQUEST_JOIN';

    /** Team's leader */
    public const string INVITE = 'TEAM_INVITE';
    public const string EDIT = 'TEAM_EDIT';
    public const string DELETE = 'TEAM_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [
            self::LEAVE,
            self::REQUEST_JOIN,
            self::INVITE,
            self::EDIT,
            self::DELETE,
        ]) && $subject instanceof Team;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        $isLeader = $subject->getLeader() === $user;
        $isMember = $subject->getUsers()->contains($user);

        return match ($attribute) {
            self::INVITE, self::DELETE, self::EDIT => $isLeader,
            self::LEAVE => $isMember,
            self::REQUEST_JOIN => !$isMember,
            default => throw new \LogicException('This code should not be reached!'),
        };
    }
}
