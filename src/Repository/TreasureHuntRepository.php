<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
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
            ->orderBy('t.title', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function findByTeam(int $id): ?TreasureHunt
    {
        return $this->createQueryBuilder('tre')
            ->leftJoin('tre.team', 'tea')
            ->addSelect('tea')
            ->leftJoin('tea.leader', 'l')
            ->addSelect('l')
            ->leftJoin('tre.riddles', 'r')
            ->addSelect('r')
            ->where('tre.id = :id')
            ->setParameter('id', $id)
            ->orderBy('r.index', \SortDirection::Ascending)
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

    /**
     * @return TreasureHunt[]
     */
    public function findByCreatorUser(User $user): array
    {
        return $this->createQueryBuilder('th')
            ->innerJoin('th.team', 't')
            ->addSelect('t')
            ->leftJoin('th.enrollments', 'e')
            ->addSelect('e')
            ->where('t.role = :role')
            ->andWhere(':user MEMBER OF t.users')
            ->setParameter('role', Team::ROLE_CREATOR)
            ->setParameter('user', $user)
            ->orderBy('th.creationDate', \SortDirection::Descending)
            ->addOrderBy('th.id', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }

    public function findWithLeaderboardData(int $id): ?TreasureHunt
    {
        return $this->createQueryBuilder('th')
            ->leftJoin('th.enrollments', 'e')
            ->addSelect('e')
            ->leftJoin('e.user', 'u')
            ->addSelect('u')
            ->leftJoin('e.team', 't')
            ->addSelect('t')
            ->where('th.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
