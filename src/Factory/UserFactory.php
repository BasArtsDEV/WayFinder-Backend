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

    private function randomUsername(): string
    {
        $base = strtolower($this->normalizeName(
            self::faker()->colorName().self::faker()->word()
        ));

        return substr($base, 0, 24).self::faker()->unique()->numberBetween(1000, 9999);
    }

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
            'username' => $this->randomUsername(),
            'isVerified' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user) {
                if (null !== $this->passwordHasher) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                }
            })
        ;
    }
}
