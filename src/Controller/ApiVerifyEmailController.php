<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class ApiVerifyEmailController extends AbstractController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET', 'POST'])]
    public function verify(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $em,
    ): JsonResponse {
        // The id parameter can come from the GET (query string)
        $id = $request->query->get('id');

        if (!$id) {
            return new JsonResponse(['message' => 'Paramètre id manquant'], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['message' => 'Email déjà vérifié'], Response::HTTP_OK);
        }

        try {
            // The helper will automatically check the full URL passed by the Request
            $verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $user->getId(),
                $user->getEmail()
            );

            $user->setIsVerified(true);
            $em->flush();

            return new JsonResponse(['message' => 'Compte activé avec succès'], Response::HTTP_OK);
        } catch (VerifyEmailExceptionInterface $e) {
            return new JsonResponse(['message' => $e->getReason()], Response::HTTP_BAD_REQUEST);
        }
    }
}
