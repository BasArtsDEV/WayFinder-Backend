<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\UserImageService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gère le traitement de l'avatar d'un utilisateur.
 *
 * POST /users/{id}/avatar et DELETE /users/{id}/avatar.
 *
 * @implements ProcessorInterface<User, User|null>
 */
readonly class UserAvatarProcessor implements ProcessorInterface
{
    public function __construct(
        private UserImageService $userImageService,
    ) {
    }

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        if ($operation instanceof Delete) {
            $this->userImageService->removeAvatar($data);

            return null;
        }

        /** @var Request $request */
        $request = $context['request'];
        $this->userImageService->setAvatar($data, $request->files->get('avatar'));

        return $data;
    }
}
