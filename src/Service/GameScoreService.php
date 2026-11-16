<?php

namespace App\Service;

use App\Entity\Enrollment;

/**
 * Computes the score and the rank of a player, solo or in a team.
 * In team mode, the score of every member is added to the team score.
 */
readonly class GameScoreService
{
    public function getScore(Enrollment $enrollment): int
    {
        $scores = $this->getCompetitorScores($enrollment);

        return $scores[$this->getCompetitorKey($enrollment)] ?? 0;
    }

    public function getRank(Enrollment $enrollment): int
    {
        $scores = $this->getCompetitorScores($enrollment);
        $score = $scores[$this->getCompetitorKey($enrollment)] ?? 0;

        return 1 + count(array_filter($scores, static fn (int $other): bool => $other > $score));
    }

    public function getCompetitorsCount(Enrollment $enrollment): int
    {
        return count($this->getCompetitorScores($enrollment));
    }

    /**
     * @return array<string, int>
     */
    private function getCompetitorScores(Enrollment $enrollment): array
    {
        $scores = [];

        foreach ($enrollment->getTreasureHunt()->getEnrollments() as $other) {
            if (null === $other->getUser()) {
                continue;
            }

            $key = $this->getCompetitorKey($other);
            $scores[$key] = ($scores[$key] ?? 0) + (int) $other->getScoreTotal();
        }

        return $scores;
    }

    private function getCompetitorKey(Enrollment $enrollment): string
    {
        $team = $enrollment->getTeam();

        return null !== $team ? 'team-'.$team->getId() : 'user-'.$enrollment->getUser()?->getId();
    }
}
