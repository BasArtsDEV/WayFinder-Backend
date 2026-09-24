<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'email' => 'admin@example.com',
            'username' => 'admin',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'roles' => ['ROLE_ADMIN'],
        ]);

        UserFactory::createOne([
            'email' => 'user@example.com',
            'username' => 'user',
            'firstName' => 'user',
            'lastName' => 'User',
            'roles' => ['ROLE_USER'],
        ]);

        UserFactory::createOne([
            'email' => 'conceptor@example.com',
            'username' => 'conceptor',
            'firstName' => 'Conceptor',
            'lastName' => 'User',
            'roles' => ['ROLE_CONCEPTOR'],
        ]);

        UserFactory::createMany(17);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TeamFixtures::class,
        ];
    }
}
