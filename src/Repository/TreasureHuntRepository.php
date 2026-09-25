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
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
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
}
