<?php

namespace App\DataFixtures;

use App\Entity\Enrollment;
use App\Factory\EnrollmentFactory;
use App\Factory\SubmissionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubmissionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $enrollments = array_values(array_filter(
            EnrollmentFactory::all(),
            static fn (Enrollment $enrollment): bool => !$enrollment->getTreasureHunt()->getRiddles()->isEmpty(),
        ));

        if ([] === $enrollments) {
            return;
        }

        SubmissionFactory::createMany(100, static function () use ($enrollments): array {
            $enrollment = $enrollments[array_rand($enrollments)];
            $riddles = $enrollment->getTreasureHunt()->getRiddles()->toArray();

            return [
                'enrollment' => $enrollment,
                'riddle' => $riddles[array_rand($riddles)],
            ];
        });

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EnrollmentFixtures::class,
            RiddleFixtures::class,
        ];
    }
}
