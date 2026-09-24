<?php

declare(strict_types=1);

namespace App\Serialization\Denormalizer;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class UserDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    public const string ALREADY_CALLED = 'USER_DENORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly Security $security,
        private readonly ?UserPasswordHasherInterface $passwordHasher = null,
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return [User::class => false];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return User::class == $type;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): User
    {
        $context[self::ALREADY_CALLED] = true;
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof PasswordAuthenticatedUserInterface) {
            throw new \LogicException('No authenticated user found.');
        }

        if (isset($data['password']) && isset($this->passwordHasher)) {
            $data['password'] = $this->passwordHasher->hashPassword($currentUser, $data['password']);
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
