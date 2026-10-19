<?php

namespace App\Factory;

use App\Entity\Team;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Team>
 */
final class TeamFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Team::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $names = json_decode(file_get_contents(__DIR__.'/../../data/team_names.json'), true);

        return [
            'name' => self::faker()->randomElement($names),
            'leader' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (Team $team): void {
                if ($team->getLeader()) {
                    if (!$team->getUsers()->contains($team->getLeader())) {
                        $team->addUser($team->getLeader());
                    }
                }
            })
        ;
    }
}
