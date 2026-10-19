<?php

namespace App\DataFixtures;

use App\Entity\Team;
use App\Entity\User;
use App\Factory\TeamFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function Zenstruck\Foundry\faker;

class TeamFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $admin = UserFactory::find(['email' => 'admin@example.com']);
        $user = UserFactory::find(['email' => 'user@example.com']);

        TeamFactory::createOne([
            'name' => 'Équipe de Admin',
            'leader' => $admin,
        ]);

        $userTeam = TeamFactory::createOne([
            'name' => 'Équipe de User',
            'leader' => $user,
        ]);

        TeamFactory::createMany(8);

        $teams = TeamFactory::all();

        $others = array_values(array_filter($teams, static fn (Team $t) => $t !== $userTeam));
        shuffle($others);
        foreach (array_slice($others, 0, 3) as $team) {
            $this->join($user, $team);
        }

        foreach (UserFactory::all() as $member) {
            if ($member === $user || !faker()->boolean()) {
                continue;
            }
            $this->join($member, $teams[array_rand($teams)]);
        }

        $manager->flush();
    }

    private function join(User $user, Team $team): void
    {
        if ($team->getUsers()->contains($user)
            || $team->getUsers()->count() >= Team::MAX_USERS
            || $user->getTeams()->count() >= User::MAX_TEAMS
        ) {
            return;
        }

        $user->addTeam($team);
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
