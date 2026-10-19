<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\TreasureHunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    /**
     * @return Query<int, Enrollment>
     */
    public function queryFeedbacksOrderedByDate(TreasureHunt $treasureHunt): Query
    {
        return $this->createQueryBuilder('e')
            ->addSelect('u')
            ->join('e.user', 'u')
            ->where('e.treasureHunt = :treasureHunt')
            ->andWhere('e.feedback IS NOT NULL')
            ->setParameter('treasureHunt', $treasureHunt)
            ->orderBy('e.registrationDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery();
    }
}
