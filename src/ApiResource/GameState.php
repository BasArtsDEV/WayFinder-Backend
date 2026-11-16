<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\GameStateProvider;

/**
 * State of a game for the authenticated player: progression, time, score, rank and current riddle.
 */
#[ApiResource(
    shortName: 'Game',
    operations: [
        new Get(
            uriTemplate: '/enrollments/{id}/game',
            uriVariables: ['id'],
            openapi: new Operation(
                summary: 'Retrieves the game state of an enrollment',
                description: 'Retrieves the progression, remaining time, score, rank and current riddle (without answers) of the authenticated player',
            ),
            security: "is_granted('ROLE_USER')",
            provider: GameStateProvider::class,
        ),
    ],
)]
final class GameState
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $enrollment;

    public string $status;

    public bool $timedOut = false;

    public int $treasureHuntId;

    public string $treasureHuntTitle;

    public ?string $teamName = null;

    public int $step = 0;

    public int $totalSteps = 0;

    public int $duration = 0;

    public int $elapsedTime = 0;

    public int $remainingTime = 0;

    public int $score = 0;

    public int $rank = 1;

    public int $competitorsCount = 1;

    public ?GameRiddle $riddle = null;
}
