<?php

namespace App\Repository;

use App\Entity\RiddleQCM;
use App\Entity\TreasureHunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiddleQCM>
 */
class RiddleQCMRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiddleQCM::class);
    }

    public function preloadChoicesForTreasureHunt(TreasureHunt $treasureHunt): void
    {
        $this->createQueryBuilder('r')
            ->leftJoin('r.choices', 'c')
            ->addSelect('c')
            ->where('r.treasureHunt = :treasureHunt')
            ->setParameter('treasureHunt', $treasureHunt)
            ->getQuery()
            ->getResult();
    }
}
