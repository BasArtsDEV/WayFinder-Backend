<?php

namespace App\DataFixtures;

use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Enum\EnrollmentStatus;
use App\Factory\EnrollmentFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EnrollmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $pairs = [];
        $treasureHunts = TreasureHuntFactory::all();
        foreach (UserFactory::all() as $user) {
            foreach ($treasureHunts as $treasureHunt) {
                $pairs[] = ['user' => $user, 'treasureHunt' => $treasureHunt];
            }
        }

        shuffle($pairs);
        foreach (array_slice($pairs, 0, 50) as $pair) {
            EnrollmentFactory::createOne($pair + $this->createProgress($pair['treasureHunt']));
        }

        $manager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function createProgress(TreasureHunt $treasureHunt): array
    {
        $riddles = $treasureHunt->getRiddles()->toArray();
        usort($riddles, static fn (Riddle $a, Riddle $b): int => $a->getIndex() <=> $b->getIndex());

        $statuses = EnrollmentStatus::cases();
        $status = [] === $riddles ? EnrollmentStatus::NOT_STARTED : $statuses[array_rand($statuses)];
        if (EnrollmentStatus::NOT_STARTED === $status) {
            return ['status' => $status, 'countdown' => 0, 'scoreTotal' => 0, 'riddle' => null];
        }

        $duration = $treasureHunt->getDuration();
        $timedOut = EnrollmentStatus::FINISHED === $status && 0 === random_int(0, 3);
        $completed = EnrollmentStatus::FINISHED === $status && !$timedOut;
        $solvedCount = $completed ? count($riddles) : random_int(0, count($riddles) - 1);
        $countdown = $timedOut ? $duration : random_int((int) ($duration * 0.2), (int) ($duration * 0.9));
        $startedAt = new \DateTimeImmutable(sprintf('-%d minutes', random_int(120, 20000)));

        return [
            'status' => $status,
            'countdown' => $countdown,
            'scoreTotal' => array_sum(array_map(static fn (Riddle $riddle): int => $riddle->getScore(), array_slice($riddles, 0, $solvedCount))),
            'riddle' => $completed ? null : $riddles[$solvedCount],
            'riddleElapsedTime' => 0,
            'startedAt' => $startedAt,
            'lastResumedAt' => EnrollmentStatus::IN_PROGRESS === $status ? new \DateTimeImmutable() : null,
            'finishedAt' => EnrollmentStatus::FINISHED === $status ? $startedAt->modify(sprintf('+%d seconds', $countdown)) : null,
        ];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TreasureHuntFixtures::class,
            RiddleFixtures::class,
        ];
    }
}
