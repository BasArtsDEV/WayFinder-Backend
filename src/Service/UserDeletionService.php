<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserDeletionService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function delete(User $user): void
    {
        $this->em->wrapInTransaction(function () use ($user) {
            // Transmettre la direction de l'équipe ou supprimer si vide
            foreach ($user->getLedTeams()->toArray() as $team) {
                $candidates = $team->getUsers()
                    ->filter(fn (User $member) => $member !== $user)
                    ->getValues();

                if ([] === $candidates) {
                    $this->em->remove($team);
                    continue;
                }

                $team->setLeader($candidates[array_rand($candidates)]);
            }

            foreach ($user->getTeams()->toArray() as $team) {
                $user->removeTeam($team);
            }

            // Va permettre d'anonymiser les participations (Joueur anonyme dans le leaderboard)
            foreach ($user->getEnrollments()->toArray() as $enrollment) {
                $enrollment->setUser(null);
            }

            $this->em->flush();
            $this->em->remove($user);
            $this->em->flush();
        });
    }
}
