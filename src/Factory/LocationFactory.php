<?php

namespace App\Factory;

use App\Entity\Location;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Location>
 */
final class LocationFactory extends PersistentObjectFactory
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
        return Location::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $names = json_decode(file_get_contents(__DIR__.'/../../data/location_names.json'), true);

        return [
            'name' => self::faker()->randomElement($names),
            'description' => self::faker()->paragraphs(3, true),
            'img' => self::faker()->imageUrl(100, 100, 'city'),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Location $location): void {})
        ;
    }
}
