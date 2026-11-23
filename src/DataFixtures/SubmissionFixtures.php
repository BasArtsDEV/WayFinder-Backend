<?php

namespace App\DataFixtures;

use App\Entity\Enrollment;
use App\Entity\Riddle;
use App\Enum\EnrollmentStatus;
use App\Factory\EnrollmentFactory;
use App\Factory\SubmissionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubmissionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (EnrollmentFactory::all() as $enrollment) {
            if (EnrollmentStatus::NOT_STARTED === $enrollment->getStatus()) {
                continue;
            }

            $riddles = $enrollment->getTreasureHunt()->getRiddles()->toArray();
            usort($riddles, static fn (Riddle $a, Riddle $b): int => $a->getIndex() <=> $b->getIndex());

            $current = $enrollment->getRiddle();
            $position = null === $current ? count($riddles) : (int) array_search($current, $riddles, true);
            $time = intdiv($enrollment->getCountdown(), $position + 1);

            foreach (array_slice($riddles, 0, $position) as $riddle) {
                $this->createAnswers($enrollment, $riddle, $time, true);
            }

            if (null !== $current) {
                $this->createAnswers($enrollment, $current, $time, false);
            }
        }

        $manager->flush();
    }

    private function createAnswers(Enrollment $enrollment, Riddle $riddle, int $time, bool $solved): void
    {
        $attributes = [
            'enrollment' => $enrollment,
            'riddle' => $riddle,
            'submissionDate' => $enrollment->getStartedAt() ?? new \DateTimeImmutable(),
        ];

        for ($i = random_int(0, 2); $i > 0; --$i) {
            SubmissionFactory::createOne($attributes + [
                'isRight' => false,
                'appliedPenalty' => $riddle->getSecondsPenalty(),
                'resolutionTime' => random_int(5, max(5, intdiv($time, 2))),
            ]);
        }

        if ($solved) {
            SubmissionFactory::createOne($attributes + [
                'isRight' => true,
                'appliedPenalty' => 0,
                'resolutionTime' => random_int(max(1, (int) ($time * 0.7)), max(1, (int) ($time * 1.3))),
            ]);
        }
    }

    public function getDependencies(): array
    {
        return [
            EnrollmentFixtures::class,
            RiddleFixtures::class,
        ];
    }
}
