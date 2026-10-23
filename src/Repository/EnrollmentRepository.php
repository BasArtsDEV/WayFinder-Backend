<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Enum\EnrollmentStatus;
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

    public function calculateTeamScore(Team $team, TreasureHunt $treasureHunt): ?float
    {
        $score = $this->createQueryBuilder('e')
            ->select('AVG(e.scoreTotal)')
            ->where('e.team = :team')
            ->andWhere('e.treasureHunt = :treasureHunt')
            ->andWhere('e.user IS NOT NULL')
            ->having('COUNT(e.id) = SUM(CASE WHEN e.status = :finished THEN 1 ELSE 0 END)')
            ->setParameter('team', $team)
            ->setParameter('treasureHunt', $treasureHunt)
            ->setParameter('finished', EnrollmentStatus::FINISHED->value)
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);

        return null !== $score ? round((float) $score, 2) : null;
    }

    /**
     * @return Query<int, Enrollment>
     */
    public function queryFeedbacksOrderedByDate(TreasureHunt $treasureHunt): Query
    {
        return $this->createQueryBuilder('e')
            ->addSelect('u')
            ->leftJoin('e.user', 'u')
            ->where('e.treasureHunt = :treasureHunt')
            ->andWhere('e.feedback IS NOT NULL')
            ->setParameter('treasureHunt', $treasureHunt)
            ->orderBy('e.registrationDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery();
    }
}
