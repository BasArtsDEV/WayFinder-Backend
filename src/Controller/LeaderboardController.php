<?php

namespace App\Controller;

use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Service\HuntStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class LeaderboardController extends AbstractController
{
    #[Route('/treasure_hunt/{id}/leaderboard', name: 'app_treasure_hunt_leaderboard', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(TreasureHunt $treasureHunt, HuntStatisticsService $huntStatisticsService): Response
    {
        $this->denyAccessUnlessCreator($treasureHunt);

        $rows = $huntStatisticsService->getDetailedLeaderboard($treasureHunt);

        return $this->render('leaderboard/index.html.twig', [
            'treasureHunt' => $treasureHunt,
            'rows' => $rows,
            'summary' => $huntStatisticsService->getSummary($treasureHunt, $rows),
            'riddleStatistics' => $huntStatisticsService->getRiddleStatistics($treasureHunt),
            'ongoingGames' => $huntStatisticsService->getOngoingGames($treasureHunt),
        ]);
    }

    private function denyAccessUnlessCreator(TreasureHunt $treasureHunt): void
    {
        $user = $this->getUser();
        $team = $treasureHunt->getTeam();
        if (!$user instanceof User || null === $team || ($team->getLeader() !== $user && !$team->getUsers()->contains($user))) {
            throw $this->createAccessDeniedException("Seuls les membres de l'équipe créatrice peuvent consulter le classement.");
        }
    }
}
