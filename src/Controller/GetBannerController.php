<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class GetBannerController extends AbstractController
{
    public function __invoke(User $data): Response
    {
        $bannerData = $data->getBanner();

        if (!$bannerData) {
            return new Response('Banner not found', Response::HTTP_NOT_FOUND);
        }

        if (str_contains($bannerData, 'base64,')) {
            $parts = explode('base64,', $bannerData);
            $bannerData = end($parts);
        }

        $binaryContent = base64_decode($bannerData, true);

        if (false === $binaryContent) {
            return new Response('Invalid image format', Response::HTTP_BAD_REQUEST);
        }

        return new Response(
            $binaryContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'image/png',
                'Content-Length' => strlen($binaryContent),
            ]
        );
    }
}
