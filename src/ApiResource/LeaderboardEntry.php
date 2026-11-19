<?php

namespace App\ApiResource;

/**
 * A player or a team in the leaderboard of a treasure hunt.
 */
final class LeaderboardEntry
{
    public int $rank;

    public string $name;

    public ?int $userId = null;

    public ?int $teamId = null;

    public int $score = 0;

    public bool $timedOut = false;

    public bool $isCurrentUser = false;
}
