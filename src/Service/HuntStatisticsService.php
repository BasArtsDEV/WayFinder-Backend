<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Enum\EnrollmentStatus;

/**
 * Results of a treasure hunt for its creators.
 *
 * @phpstan-import-type LeaderboardEntry from GameScoreService
 *
 * @phpstan-type LeaderboardRow array{entry: LeaderboardEntry, points: int, bonus: int, playTime: int, players: int, finishedAt: ?\DateTimeImmutable}
 */
readonly class HuntStatisticsService
{
    public function __construct(
        private GameScoreService $gameScoreService,
        private GameProgressionService $gameProgressionService,
    ) {
    }

    /**
     * @return list<LeaderboardRow>
     */
    public function getDetailedLeaderboard(TreasureHunt $treasureHunt): array
    {
        $finished = [];
        foreach ($this->getPlayedEnrollments($treasureHunt) as $enrollment) {
            if (EnrollmentStatus::FINISHED === $enrollment->getStatus()) {
                $finished[$this->gameScoreService->getCompetitorKey($enrollment)][] = $enrollment;
            }
        }

        $rows = [];
        foreach ($this->gameScoreService->getLeaderboard($treasureHunt) as $entry) {
            $enrollments = $finished[$entry['key']] ?? [];
            $finishedAt = null;
            foreach ($enrollments as $enrollment) {
                if (null === $finishedAt || $enrollment->getFinishedAt() > $finishedAt) {
                    $finishedAt = $enrollment->getFinishedAt();
                }
            }

            $rows[] = [
                'entry' => $entry,
                'points' => $this->average(array_map(static fn (Enrollment $e): int => (int) $e->getScoreTotal(), $enrollments)),
                'bonus' => $this->average(array_map(fn (Enrollment $e): int => $this->gameProgressionService->getRemainingTime($e), $enrollments)),
                'playTime' => $this->average(array_map(fn (Enrollment $e): int => $this->getPlayTime($e), $enrollments)),
                'players' => count($enrollments),
                'finishedAt' => $finishedAt,
            ];
        }

        return $rows;
    }

    /**
     * @param list<LeaderboardRow> $rows
     *
     * @return array{players: int, started: int, ongoing: int, finished: int, completed: int, timedOut: int, completionRate: ?int, averageScore: ?int, bestScore: ?int, averagePlayTime: ?int}
     */
    public function getSummary(TreasureHunt $treasureHunt, array $rows): array
    {
        $enrollments = $this->getPlayedEnrollments($treasureHunt);
        $started = array_filter($enrollments, static fn (Enrollment $e): bool => EnrollmentStatus::NOT_STARTED !== $e->getStatus());
        $finished = array_filter($enrollments, static fn (Enrollment $e): bool => EnrollmentStatus::FINISHED === $e->getStatus());
        $timedOut = array_filter($finished, fn (Enrollment $e): bool => $this->gameProgressionService->isTimedOut($e));
        $completed = count($finished) - count($timedOut);
        $scores = array_map(static fn (array $row): int => $row['entry']['score'], $rows);

        return [
            'players' => count($enrollments),
            'started' => count($started),
            'ongoing' => count($started) - count($finished),
            'finished' => count($finished),
            'completed' => $completed,
            'timedOut' => count($timedOut),
            'completionRate' => [] === $started ? null : (int) round($completed * 100 / count($started)),
            'averageScore' => [] === $scores ? null : $this->average($scores),
            'bestScore' => [] === $scores ? null : max($scores),
            'averagePlayTime' => [] === $finished ? null : $this->average(array_map(fn (Enrollment $e): int => $this->getPlayTime($e), $finished)),
        ];
    }

    /**
     * @return list<array{riddle: Riddle, attempts: int, solved: int, wrongAnswers: int, successRate: ?int, averageTime: ?int, blocked: int, isHardest: bool}>
     */
    public function getRiddleStatistics(TreasureHunt $treasureHunt): array
    {
        $riddles = $treasureHunt->getRiddles()->toArray();
        usort($riddles, static fn (Riddle $a, Riddle $b): int => $a->getIndex() <=> $b->getIndex());

        $statistics = [];
        foreach ($riddles as $riddle) {
            $attempts = [];
            $solved = [];
            $wrongAnswers = 0;
            $times = [];

            foreach ($riddle->getSubmissions() as $submission) {
                if ($submission->getEnrollment()?->getTreasureHunt() !== $treasureHunt) {
                    continue;
                }

                $enrollmentId = $submission->getEnrollment()->getId();
                $attempts[$enrollmentId] = true;

                if ($submission->isRight()) {
                    $solved[$enrollmentId] = true;
                    $times[] = (int) $submission->getResolutionTime();
                } else {
                    ++$wrongAnswers;
                }
            }

            $blocked = 0;
            foreach ($riddle->getEnrollments() as $enrollment) {
                if ($enrollment->getTreasureHunt() === $treasureHunt && EnrollmentStatus::FINISHED === $enrollment->getStatus()) {
                    ++$blocked;
                }
            }

            $statistics[] = [
                'riddle' => $riddle,
                'attempts' => count($attempts),
                'solved' => count($solved),
                'wrongAnswers' => $wrongAnswers,
                'successRate' => [] === $attempts ? null : (int) round(count($solved) * 100 / count($attempts)),
                'averageTime' => [] === $times ? null : $this->average($times),
                'blocked' => $blocked,
                'isHardest' => false,
            ];
        }

        $hardest = null;
        foreach ($statistics as $index => $statistic) {
            if (null === $statistic['successRate'] || $statistic['successRate'] >= 100) {
                continue;
            }

            if (null === $hardest || $statistic['successRate'] < $statistics[$hardest]['successRate']) {
                $hardest = $index;
            }
        }

        if (null !== $hardest) {
            $statistics[$hardest]['isHardest'] = true;
        }

        return $statistics;
    }

    /**
     * @return list<Enrollment>
     */
    public function getOngoingGames(TreasureHunt $treasureHunt): array
    {
        $games = array_values(array_filter(
            $this->getPlayedEnrollments($treasureHunt),
            static fn (Enrollment $e): bool => in_array($e->getStatus(), [EnrollmentStatus::IN_PROGRESS, EnrollmentStatus::PAUSED], true),
        ));

        usort($games, static fn (Enrollment $a, Enrollment $b): int => $b->getScoreTotal() <=> $a->getScoreTotal());

        return $games;
    }

    public function getPlayTime(Enrollment $enrollment): int
    {
        return min($enrollment->getTreasureHunt()->getDuration(), $enrollment->getCountdown());
    }

    /**
     * @return list<Enrollment>
     */
    private function getPlayedEnrollments(TreasureHunt $treasureHunt): array
    {
        return array_values(array_filter(
            $treasureHunt->getEnrollments()->toArray(),
            static fn (Enrollment $e): bool => null !== $e->getUser(),
        ));
    }

    /**
     * @param list<int> $values
     */
    private function average(array $values): int
    {
        if ([] === $values) {
            return 0;
        }

        return (int) round(array_sum($values) / count($values));
    }
}
