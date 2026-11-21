<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Retrieves all notifications intended for a given user.
     *
     * @return Notification[]
     */
    public function findByAny(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.receiveNotif', 'u')
            ->leftJoin('n.targetUser', 'tu')
            ->addSelect('tu')
            ->leftJoin('n.targetTeam', 'tt')
            ->addSelect('tt')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('n.date', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Notification[]
     */
    public function findByUserWithRecipients(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.receiveNotif', 'u')
            ->leftJoin('n.receiveNotif', 'r')
            ->addSelect('r')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->innerJoin('n.receiveNotif', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param User[] $users
     *
     * @return Notification[]
     */
    public function findPendingTeamNotifications(Team $team, array $users): array
    {
        if ([] === $users) {
            return [];
        }

        return $this->createQueryBuilder('n')
            ->leftJoin('n.targetUser', 'tu')
            ->addSelect('tu')
            ->where('n.targetTeam = :team')
            ->andWhere('n.targetUser IN (:users)')
            ->andWhere('n.type IN (:types)')
            ->setParameter('team', $team)
            ->setParameter('users', $users)
            ->setParameter('types', [
                Notification::TYPE_TEAM_INVITATION,
                Notification::TYPE_TEAM_JOIN_REQUEST,
            ])
            ->getQuery()
            ->getResult();
    }
}
