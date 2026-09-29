<?php

namespace App\Factory;

use App\Entity\TreasureHunt;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<TreasureHunt>
 */
final class TreasureHuntFactory extends PersistentObjectFactory
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
        return TreasureHunt::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $titles = json_decode(file_get_contents(__DIR__.'/../../data/treasurehunts_names.json'), true);

        return [
            'creationDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'description' => self::faker()->text(),
            'invitationCode' => strtoupper(self::faker()->bothify('??????')),
            'published' => self::faker()->boolean(),
            'team' => TeamFactory::random(),
            'title' => self::faker()->randomElement($titles),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(TreasureHunt $treasureHunt): void {})
        ;
    }
}
