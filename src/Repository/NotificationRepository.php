<?php

namespace App\Repository;

use App\Entity\Notification;
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
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('n.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
