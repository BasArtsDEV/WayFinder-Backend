<?php

namespace App\Factory;

use App\Entity\RiddleQCM;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RiddleQCM>
 */
final class RiddleQCMFactory extends PersistentObjectFactory
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
        return RiddleQCM::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->text(),
            'index' => self::faker()->numberBetween(1, 10),
            'location' => LocationFactory::random(),
            'question' => self::faker()->text(1024),
            'secondsPenalty' => self::faker()->randomElement([15, 30, 60]),
            'title' => self::faker()->text(120),
            'treasureHunt' => TreasureHuntFactory::random(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(RiddleQCM $riddleQCM): void {})
        ;
    }
}
