<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AvatarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetAvatarController extends AbstractController
{
    public function __construct(
        private readonly AvatarService $avatarService,
    ) {
    }

    public function __invoke(User $data): Response
    {
        return $this->avatarService->getAvatarResponse($data);
    }
}
