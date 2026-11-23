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
        $descriptions = json_decode(file_get_contents(__DIR__.'/../../data/riddle_descriptions.json'), true);
        $questions = json_decode(file_get_contents(__DIR__.'/../../data/riddle_questions.json'), true);

        return [
            'description' => self::faker()->randomElement($descriptions),
            'index' => self::faker()->numberBetween(1, 10),
            'location' => LocationFactory::random(),
            'question' => self::faker()->randomElement($questions),
            'secondsPenalty' => self::faker()->randomElement([15, 30, 60]),
            'score' => self::faker()->randomElement([10, 20, 50, 100]),
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
