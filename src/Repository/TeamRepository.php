<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * @return Team[]
     */
    public function findAllTeamsOrderedByName(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTeamWithMembers(int $id): ?Team
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leader', 'l')
            ->addSelect('l')
            ->leftJoin('t.users', 'u')
            ->addSelect('u')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Query<int, Team>
     */
    public function queryForList(User $user, string $search, TeamFilter $filter): Query
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.leader', 'l')->addSelect('l')
            ->leftJoin('t.users', 'u')->addSelect('u')
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('t.id', 'ASC');

        match ($filter) {
            TeamFilter::Member => $qb->andWhere(':user MEMBER OF t.users')->setParameter('user', $user),
            TeamFilter::Leader => $qb->andWhere('t.leader = :user')->setParameter('user', $user),
            TeamFilter::All => null,
        };

        if ('' !== $search) {
            $qb->andWhere($qb->expr()->orX(
                'LOWER(t.name) LIKE :search',
                'LOWER(l.username) LIKE :search',
            ))->setParameter('search', '%'.addcslashes(mb_strtolower($search), '%_\\').'%');
        }

        return $qb->getQuery();
    }

    public function findBannerById(int $id): ?string
    {
        return $this->createQueryBuilder('t')
            ->select('t.banner')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}
