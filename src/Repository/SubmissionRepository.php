<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Submission;
use App\Entity\TreasureHunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Submission>
 */
class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function deleteByTreasureHunt(TreasureHunt $treasureHunt): void
    {
        $em = $this->getEntityManager();
        $em->createQuery(
            'DELETE FROM '.Submission::class.' s WHERE s.enrollment IN (SELECT e.id FROM '.Enrollment::class.' e WHERE e.treasureHunt = :treasureHunt)'
        )
            ->setParameter('treasureHunt', $treasureHunt)
            ->execute();
    }
}
