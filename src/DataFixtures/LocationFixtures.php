<?php

namespace App\DataFixtures;

use App\Factory\LocationFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        LocationFactory::createMany(10);

        $manager->flush();
    }
}
