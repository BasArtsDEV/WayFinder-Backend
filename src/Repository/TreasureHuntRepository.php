<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TreasureHunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TreasureHunt>
 */
class TreasureHuntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreasureHunt::class);
    }

    /**
     * @return TreasureHunt[]
     */
    public function findByTeamOrdered(Team $team): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.team = :team')
            ->setParameter('team', $team)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeam(int $id): ?TreasureHunt
    {
        return $this->createQueryBuilder('tre')
            ->leftJoin('tre.team', 'tea')
            ->addSelect('tea')
            ->leftJoin('tre.riddles', 'r')
            ->addSelect('r')
            ->where('tre.id = :id')
            ->setParameter('id', $id)
            ->orderBy('r.index', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return TreasureHunt[]
     */
    public function findForTeamWithEnrollments(Team $team): array
    {
        return $this->createQueryBuilder('th')
            ->leftJoin('th.enrollments', 'e')
            ->addSelect('e')
            ->where('th.team = :team')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();
    }
}
