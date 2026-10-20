<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class GetAvatarController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $appKernel,
    ) {
    }

    public function __invoke(User $data): Response
    {
        if (null === $data->getAvatar()) {
            $defaultImagePath = $this->appKernel->getProjectDir().'/assets/img/default-avatar.png';

            if (!file_exists($defaultImagePath)) {
                throw $this->createNotFoundException('Default avatar not found');
            }

            return new BinaryFileResponse($defaultImagePath);
        }

        return new Response(
            stream_get_contents($data->getAvatar(), -1, 0),
            Response::HTTP_OK,
            ['content-type' => 'image/png']
        );
    }
}
