<?php

namespace App\DataFixtures;

use App\Factory\TreasureHuntFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TreasureHuntFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        TreasureHuntFactory::createMany(15);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TeamFixtures::class];
    }
}
