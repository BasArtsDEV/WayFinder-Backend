<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    private \Transliterator $transliterator;

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private readonly ?UserPasswordHasherInterface $passwordHasher = null,
    ) {
        parent::__construct();
        $this->transliterator = \Transliterator::create('Any-Lower; Latin-ASCII');
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    protected function normalizeName(string $name): string
    {
        $nameTransliterated = $this->transliterator->transliterate($name);

        return preg_replace('/[^a-zA-Z]/', '-', $nameTransliterated);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $domainName = self::faker()->domainName();
        $firstName = self::faker()->firstName();
        $lastName = self::faker()->lastName();

        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'email' => "{$this->normalizeName($firstName.$lastName)}@$domainName",
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => 'mdp',
            'roles' => ['ROLE_USER'],
            'username' => self::faker()->unique()->randomElement([
                strtolower(self::faker()->colorName().self::faker()->word()),
                strtolower(self::faker()->dateTime()->format('M').self::faker()->firstName()),
            ]),
            'avatar' => self::faker()->imageUrl(100, 100, 'people'),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user) {
                if (null !== $this->passwordHasher) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                }

                if (self::faker()->boolean()) {
                    $team = TeamFactory::randomOrCreate();
                    $user->addTeam($team);
                }
            })
        ;
    }
}
