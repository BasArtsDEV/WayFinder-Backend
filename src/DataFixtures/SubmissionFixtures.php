<?php

namespace App\DataFixtures;

use App\Factory\SubmissionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubmissionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        SubmissionFactory::createMany(100);

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
