<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
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

    public function findWithGameContext(int $id): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.treasureHunt', 'th')
            ->addSelect('th')
            ->leftJoin('e.user', 'u')
            ->addSelect('u')
            ->leftJoin('e.team', 't')
            ->addSelect('t')
            ->leftJoin('e.riddle', 'r')
            ->addSelect('r')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Enrollment[]
     */
    public function findForTreasureHuntWithCompetitors(TreasureHunt $treasureHunt): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->addSelect('u')
            ->leftJoin('e.team', 't')
            ->addSelect('t')
            ->where('e.treasureHunt = :treasureHunt')
            ->setParameter('treasureHunt', $treasureHunt)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User[] $users
     */
    public function findOneByUsersAndTreasureHunt(array $users, TreasureHunt $treasureHunt): ?Enrollment
    {
        if ([] === $users) {
            return null;
        }

        return $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u')
            ->addSelect('u')
            ->where('e.treasureHunt = :treasureHunt')
            ->andWhere('e.user IN (:users)')
            ->setParameter('treasureHunt', $treasureHunt)
            ->setParameter('users', $users)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function resetByTreasureHunt(TreasureHunt $treasureHunt): void
    {
        $this->createQueryBuilder('e')
            ->update()
            ->set('e.status', ':status')
            ->set('e.riddle', 'NULL')
            ->set('e.scoreTotal', '0')
            ->set('e.countdown', '0')
            ->set('e.riddleElapsedTime', '0')
            ->set('e.startedAt', 'NULL')
            ->set('e.lastResumedAt', 'NULL')
            ->set('e.finishedAt', 'NULL')
            ->where('e.treasureHunt = :treasureHunt')
            ->setParameter('status', EnrollmentStatus::NOT_STARTED)
            ->setParameter('treasureHunt', $treasureHunt)
            ->getQuery()
            ->execute();
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
