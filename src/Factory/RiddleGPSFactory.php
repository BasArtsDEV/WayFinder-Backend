<?php

namespace App\Factory;

use App\Entity\RiddleGPS;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RiddleGPS>
 */
final class RiddleGPSFactory extends PersistentObjectFactory
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
        return RiddleGPS::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $faker = self::faker();
        $descriptions = json_decode(file_get_contents(__DIR__.'/../../data/riddle_descriptions.json'), true);

        return [
            'description' => $faker->randomElement($descriptions),
            'index' => $faker->numberBetween(1, 10),
            'location' => LocationFactory::random(),
            'latitude' => $faker->latitude(48.8, 48.9),
            'longitude' => $faker->longitude(2.2, 2.4),
            'secondsPenalty' => $faker->randomElement([15, 30, 60]),
            'title' => $faker->text(120),
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
            // ->afterInstantiate(function(RiddleGPS $riddleGPS): void {})
        ;
    }
}
