<?php

namespace App\Factory;

use App\Entity\Enrollment;
use App\Enum\EnrollmentStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Enrollment>
 */
final class EnrollmentFactory extends PersistentObjectFactory
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
        return Enrollment::class;
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
            'status' => self::faker()->randomElement(EnrollmentStatus::cases()),
            'treasureHunt' => TreasureHuntFactory::random(),
            'user' => UserFactory::random(),
            'riddle' => null,
            'nbStars' => self::faker()->numberBetween(0, 5),
            'feedback' => self::faker()->text(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Enrollment $enrollment): void {})
        ;
    }
}
