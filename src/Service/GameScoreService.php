<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Enum\EnrollmentStatus;

/**
 * Computes the score and the rank of a player, solo or in a team.
 * During a game, the score of every member is added to the team score.
 * Once finished, the remaining seconds are added to the score and a team gets the average of its members.
 *
 * @phpstan-type LeaderboardEntry array{key: string, rank: int, score: int, timedOut: bool, user: ?User, team: ?Team, isCurrentUser: bool}
 */
readonly class GameScoreService
{
    public function __construct(
        private GameProgressionService $gameProgressionService,
    ) {
    }

    /**
     * @param iterable<Enrollment>|null $enrollments
     *
     * @return array{score: int, rank: int, competitorsCount: int}
     */
    public function getStanding(Enrollment $enrollment, ?iterable $enrollments = null): array
    {
        $enrollments ??= $enrollment->getTreasureHunt()->getEnrollments();

        if (EnrollmentStatus::FINISHED === $enrollment->getStatus()) {
            $leaderboard = $this->getLeaderboard($enrollment->getTreasureHunt(), null, $enrollments);
            $key = $this->getCompetitorKey($enrollment);

            foreach ($leaderboard as $entry) {
                if ($entry['key'] === $key) {
                    return [
                        'score' => $entry['score'],
                        'rank' => $entry['rank'],
                        'competitorsCount' => count($leaderboard),
                    ];
                }
            }
        }

        $scores = $this->getCompetitorScores($enrollment, $enrollments);
        $score = $scores[$this->getCompetitorKey($enrollment)] ?? 0;

        return [
            'score' => $score,
            'rank' => 1 + count(array_filter($scores, static fn (int $other): bool => $other > $score)),
            'competitorsCount' => count($scores),
        ];
    }

    public function getScore(Enrollment $enrollment): int
    {
        return $this->getStanding($enrollment)['score'];
    }

    public function getRank(Enrollment $enrollment): int
    {
        return $this->getStanding($enrollment)['rank'];
    }

    public function getCompetitorsCount(Enrollment $enrollment): int
    {
        return $this->getStanding($enrollment)['competitorsCount'];
    }

    public function getFinalScore(Enrollment $enrollment): int
    {
        return $enrollment->getScoreTotal() + $this->gameProgressionService->getRemainingTime($enrollment);
    }

    /**
     * Finished competitors of a treasure hunt, from the best to the worst score.
     *
     * @param iterable<Enrollment>|null $enrollments
     *
     * @return list<LeaderboardEntry>
     */
    public function getLeaderboard(TreasureHunt $treasureHunt, ?User $currentUser = null, ?iterable $enrollments = null): array
    {
        $competitors = [];

        foreach ($enrollments ?? $treasureHunt->getEnrollments() as $enrollment) {
            $user = $enrollment->getUser();
            if (null === $user) {
                continue;
            }

            $key = $this->getCompetitorKey($enrollment);
            $competitors[$key] ??= [
                'key' => $key,
                'scores' => [],
                'timedOut' => true,
                'finishedAt' => null,
                'user' => null === $enrollment->getTeam() ? $user : null,
                'team' => $enrollment->getTeam(),
                'isCurrentUser' => false,
            ];

            if (null !== $currentUser && $user === $currentUser) {
                $competitors[$key]['isCurrentUser'] = true;
            }

            if (EnrollmentStatus::FINISHED !== $enrollment->getStatus()) {
                continue;
            }

            $competitors[$key]['scores'][] = $this->getFinalScore($enrollment);
            $competitors[$key]['timedOut'] = $competitors[$key]['timedOut'] && $this->gameProgressionService->isTimedOut($enrollment);

            $finishedAt = $enrollment->getFinishedAt();
            if (null === $competitors[$key]['finishedAt'] || (null !== $finishedAt && $finishedAt < $competitors[$key]['finishedAt'])) {
                $competitors[$key]['finishedAt'] = $finishedAt;
            }
        }

        $leaderboard = [];
        foreach ($competitors as $competitor) {
            if ([] === $competitor['scores']) {
                continue;
            }

            $competitor['score'] = (int) round(array_sum($competitor['scores']) / count($competitor['scores']));
            $leaderboard[] = $competitor;
        }

        usort($leaderboard, static fn (array $a, array $b): int => [$b['score'], $a['finishedAt']] <=> [$a['score'], $b['finishedAt']]);

        $entries = [];
        $previousScore = null;
        $rank = 0;
        foreach ($leaderboard as $index => $competitor) {
            if ($previousScore !== $competitor['score']) {
                $rank = $index + 1;
                $previousScore = $competitor['score'];
            }

            $entries[] = [
                'key' => $competitor['key'],
                'rank' => $rank,
                'score' => $competitor['score'],
                'timedOut' => $competitor['timedOut'],
                'user' => $competitor['user'],
                'team' => $competitor['team'],
                'isCurrentUser' => $competitor['isCurrentUser'],
            ];
        }

        return $entries;
    }

    /**
     * @param iterable<Enrollment>|null $enrollments
     *
     * @return array<string, int>
     */
    private function getCompetitorScores(Enrollment $enrollment, ?iterable $enrollments = null): array
    {
        $scores = [];

        foreach ($enrollments ?? $enrollment->getTreasureHunt()->getEnrollments() as $other) {
            if (null === $other->getUser()) {
                continue;
            }

            $key = $this->getCompetitorKey($other);
            $scores[$key] = ($scores[$key] ?? 0) + (int) $other->getScoreTotal();
        }

        return $scores;
    }

    public function getCompetitorKey(Enrollment $enrollment): string
    {
        $team = $enrollment->getTeam();

        return null !== $team ? 'team-'.$team->getId() : 'user-'.$enrollment->getUser()?->getId();
    }
}
