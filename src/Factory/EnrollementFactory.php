<?php

namespace App\Factory;

use App\Entity\Enrollement;
use App\Enum\EnrollementStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Enrollement>
 */
final class EnrollementFactory extends PersistentObjectFactory
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
        return Enrollement::class;
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
            'countdown' => self::faker()->randomNumber(),
            'registrationDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'scoreTotal' => self::faker()->randomNumber(),
            'status' => self::faker()->randomElement(EnrollementStatus::cases()),
            'treasureHunt' => TreasureHuntFactory::random(),
            'users' => [UserFactory::random()],
            'riddle' => null,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Enrollement $enrollement): void {})
        ;
    }
}
