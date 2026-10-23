<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Repository\EnrollmentRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TeamScoreController extends AbstractController
{
    #[Route(
        '/api/teams/{teamId}/treasure_hunts/{treasureHuntId}/score',
        name: 'api_team_score',
        requirements: ['teamId' => '\d+', 'treasureHuntId' => '\d+'],
        methods: ['GET'],
        format: 'json',
    )]
    #[IsGranted('ROLE_USER')]
    public function __invoke(
        #[MapEntity(id: 'teamId')] Team $team,
        #[MapEntity(id: 'treasureHuntId')] TreasureHunt $treasureHunt,
        EnrollmentRepository $enrollmentRepository,
    ): JsonResponse {
        return new JsonResponse([
            'teamScore' => $enrollmentRepository->calculateTeamScore($team, $treasureHunt),
        ]);
    }
}
