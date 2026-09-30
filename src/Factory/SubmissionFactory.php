<?php

namespace App\Factory;

use App\Entity\Submission;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Submission>
 */
final class SubmissionFactory extends PersistentObjectFactory
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
        return Submission::class;
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
            'appliedPenalty' => self::faker()->randomElement([0, 15, 30, 60]),
            'enrollment' => EnrollmentFactory::random(),
            'isRight' => self::faker()->boolean(),
            'resolutionTime' => self::faker()->randomNumber(),
            'submissionDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'submittedValue' => self::faker()->text(255),
            'riddle' => self::faker()->randomElement([
                RiddleGPSFactory::random(),
                RiddleQCMFactory::random(),
                RiddleQRCodeFactory::random(),
                RiddleTextFactory::random(),
            ]),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Submission $submission): void {})
        ;
    }
}
