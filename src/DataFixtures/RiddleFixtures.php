<?php

namespace App\DataFixtures;

use App\Factory\RiddleGPSFactory;
use App\Factory\RiddleQCMFactory;
use App\Factory\RiddleQRCodeFactory;
use App\Factory\RiddleTextFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RiddleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        RiddleGPSFactory::createMany(5);
        RiddleQCMFactory::createMany(5);
        RiddleQRCodeFactory::createMany(5);
        RiddleTextFactory::createMany(5);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TreasureHuntFixtures::class,
            LocationFixtures::class,
        ];
    }
}
