<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User, User|void>
 */
readonly class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        /** @var ProcessorInterface<User, User|void> */
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $data->setPassword(
            $this->passwordHasher->hashPassword($data, $data->getPassword())
        );

        $data->setRoles(['ROLE_USER']);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
