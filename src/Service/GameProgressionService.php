<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Riddle;
use App\Enum\EnrollmentStatus;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

readonly class GameProgressionService
{
    public function transition(Enrollment $enrollment, ?EnrollmentStatus $target): void
    {
        $current = $enrollment->getStatus();
        if (null === $target || $target === $current) {
            $this->updateTimers($enrollment);

            return;
        }

        $now = new \DateTimeImmutable();

        if (EnrollmentStatus::IN_PROGRESS === $target && EnrollmentStatus::NOT_STARTED === $current) {
            $riddles = $this->getOrderedRiddles($enrollment);
            if ([] === $riddles) {
                throw new UnprocessableEntityHttpException('Cette chasse ne contient aucune énigme.');
            }

            $enrollment->setRiddle($riddles[0])
                ->setScoreTotal(0)
                ->setCountdown(0)
                ->setRiddleElapsedTime(0)
                ->setStartedAt($now);
        } elseif (EnrollmentStatus::IN_PROGRESS === $target && EnrollmentStatus::PAUSED === $current) {
            if (null === $enrollment->getRiddle()) {
                throw new ConflictHttpException('Cette partie ne possède pas d\'énigme courante.');
            }
        } elseif (EnrollmentStatus::PAUSED === $target && EnrollmentStatus::IN_PROGRESS === $current) {
            $this->updateTimers($enrollment, $now);
            $enrollment->setStatus($target)->setLastResumedAt(null);

            return;
        } else {
            throw new ConflictHttpException('Cette transition de partie n\'est pas autorisée.');
        }

        $enrollment->setStatus($target)->setLastResumedAt($now);
    }

    public function updateTimers(Enrollment $enrollment, ?\DateTimeImmutable $now = null): void
    {
        if (EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus() || null === $enrollment->getLastResumedAt()) {
            return;
        }

        if (null === $now) {
            $now = new \DateTimeImmutable();
        }

        $elapsed = max(0, $now->getTimestamp() - $enrollment->getLastResumedAt()->getTimestamp());
        $enrollment->setCountdown($enrollment->getCountdown($now));
        $enrollment->setRiddleElapsedTime($enrollment->getRiddleElapsedTime() + $elapsed);
        $enrollment->setLastResumedAt($now);

        $this->finishIfTimeIsUp($enrollment, $now);
    }

    public function reset(Enrollment $enrollment): void
    {
        $enrollment->getSubmissions()->clear();

        $enrollment->setStatus(EnrollmentStatus::NOT_STARTED)
            ->setRiddle(null)
            ->setScoreTotal(0)
            ->setCountdown(0)
            ->setRiddleElapsedTime(0)
            ->setStartedAt(null)
            ->setLastResumedAt(null)
            ->setFinishedAt(null);
    }

    public function getRemainingTime(Enrollment $enrollment, ?\DateTimeImmutable $now = null): int
    {
        return max(0, $enrollment->getTreasureHunt()->getDuration() - $enrollment->getCountdown($now));
    }

    public function isTimedOut(Enrollment $enrollment): bool
    {
        return EnrollmentStatus::FINISHED === $enrollment->getStatus() && null !== $enrollment->getRiddle();
    }

    private function finishIfTimeIsUp(Enrollment $enrollment, \DateTimeImmutable $now): void
    {
        if (EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus() || $this->getRemainingTime($enrollment, $now) > 0) {
            return;
        }

        $enrollment->setCountdown($enrollment->getTreasureHunt()->getDuration())
            ->setStatus(EnrollmentStatus::FINISHED)
            ->setLastResumedAt(null)
            ->setFinishedAt($now);
    }

    public function applyAnswer(Enrollment $enrollment, bool $isRight): int
    {
        $riddle = $enrollment->getRiddle();
        if (null === $riddle || EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus()) {
            throw new ConflictHttpException('La partie doit être en cours.');
        }

        $now = new \DateTimeImmutable();
        $this->updateTimers($enrollment, $now);
        if (EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus()) {
            throw new ConflictHttpException('Le temps imparti est écoulé.');
        }

        $resolutionTime = $enrollment->getRiddleElapsedTime();

        if (!$isRight) {
            $penalty = max(0, $riddle->getSecondsPenalty());
            $enrollment->setCountdown($enrollment->getCountdown($now) + $penalty);
            $this->finishIfTimeIsUp($enrollment, $now);

            return $resolutionTime;
        }

        $riddles = $this->getOrderedRiddles($enrollment);
        $position = array_search($riddle, $riddles, true);
        if (false === $position) {
            throw new ConflictHttpException('L\'énigme courante n\'appartient plus à cette chasse.');
        }

        $enrollment->setScoreTotal($enrollment->getScoreTotal() + $riddle->getScore());
        $enrollment->setRiddle($riddles[$position + 1] ?? null)->setRiddleElapsedTime(0);

        if (null === $enrollment->getRiddle()) {
            $enrollment->setStatus(EnrollmentStatus::FINISHED)
                ->setLastResumedAt(null)
                ->setFinishedAt($now);
        }

        return $resolutionTime;
    }

    /**
     * @return Riddle[]
     */
    private function getOrderedRiddles(Enrollment $enrollment): array
    {
        $riddles = $enrollment->getTreasureHunt()->getRiddles()->toArray();
        usort($riddles, fn (Riddle $a, Riddle $b) => $a->getIndex() <=> $b->getIndex());

        return $riddles;
    }
}
