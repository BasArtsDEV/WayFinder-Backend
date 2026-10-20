<?php

namespace App\DataFixtures;

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
            EnrollmentFactory::createOne($pair);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TreasureHuntFixtures::class,
        ];
    }
}
