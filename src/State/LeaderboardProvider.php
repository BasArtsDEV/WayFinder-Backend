<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Leaderboard;
use App\ApiResource\LeaderboardEntry;
use App\Entity\User;
use App\Repository\TreasureHuntRepository;
use App\Service\GameProgressionService;
use App\Service\GameScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Leaderboard>
 */
readonly class LeaderboardProvider implements ProviderInterface
{
    public function __construct(
        private TreasureHuntRepository $treasureHuntRepository,
        private EntityManagerInterface $em,
        private Security $security,
        private GameProgressionService $gameProgressionService,
        private GameScoreService $gameScoreService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Leaderboard
    {
        $treasureHunt = $this->treasureHuntRepository->find($uriVariables['id'] ?? 0);
        if (null === $treasureHunt || !$treasureHunt->isPublished()) {
            throw new NotFoundHttpException('Cette chasse au trésor n\'est pas disponible.');
        }

        foreach ($treasureHunt->getEnrollments() as $enrollment) {
            $this->gameProgressionService->updateTimers($enrollment);
        }
        $this->em->flush();

        $user = $this->security->getUser();

        $leaderboard = new Leaderboard();
        $leaderboard->id = (int) $treasureHunt->getId();
        $leaderboard->treasureHuntTitle = (string) $treasureHunt->getTitle();

        foreach ($this->gameScoreService->getLeaderboard($treasureHunt, $user instanceof User ? $user : null) as $competitor) {
            $entry = new LeaderboardEntry();
            $entry->rank = $competitor['rank'];
            $entry->score = $competitor['score'];
            $entry->timedOut = $competitor['timedOut'];
            $entry->isCurrentUser = $competitor['isCurrentUser'];

            if (null !== $competitor['team']) {
                $entry->name = $competitor['team']->getName();
                $entry->teamId = $competitor['team']->getId();
            } else {
                $entry->name = (string) $competitor['user']?->getUsername();
                $entry->userId = $competitor['user']?->getId();
            }

            $leaderboard->entries[] = $entry;
        }

        return $leaderboard;
    }
}
