<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
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
}
