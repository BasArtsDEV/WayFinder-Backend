<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

readonly class AvatarService
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function getAvatarResponse(User $user): Response
    {
        if (null === $user->getAvatar()) {
            $defaultImagePath = $this->kernel->getProjectDir().'/assets/img/default-avatar.png';

            if (!file_exists($defaultImagePath)) {
                throw new NotFoundHttpException('Default avatar not found');
            }

            return new BinaryFileResponse($defaultImagePath);
        }

        $avatarData = is_resource($user->getAvatar())
            ? stream_get_contents($user->getAvatar())
            : $user->getAvatar();

        return new Response($avatarData, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
        ]);
    }
}
