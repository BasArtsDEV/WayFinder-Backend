<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\LeaderboardProvider;

/**
 * Leaderboard of a treasure hunt: players and teams who finished their game.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/treasure_hunts/{id}/leaderboard',
            uriVariables: ['id'],
            openapi: new Operation(
                summary: 'Retrieves the leaderboard of a treasure hunt',
                description: 'Retrieves the finished players and teams, sorted by their final score (points and remaining seconds)',
            ),
            provider: LeaderboardProvider::class,
        ),
    ],
)]
final class Leaderboard
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $treasureHuntTitle;

    /**
     * @var list<LeaderboardEntry>
     */
    public array $entries = [];
}
