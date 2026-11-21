<?php

namespace App\Repository;

use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Riddle>
 */
class RiddleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Riddle::class);
    }

    /**
     * @return Riddle[]
     */
    public function findOrderedByTreasureHunt(TreasureHunt $treasureHunt): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.treasureHunt = :treasureHunt')
            ->setParameter('treasureHunt', $treasureHunt)
            ->orderBy('r.index', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, Riddle>
     */
    public function findByIdsIndexed(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('r', 'r.id')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
