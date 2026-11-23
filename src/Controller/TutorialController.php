<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class TutorialController extends AbstractController
{
    #[Route('/tutorial/complete', name: 'app_tutorial_complete', methods: ['POST'])]
    public function complete(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->headers->get('X-CSRF-TOKEN');
        if (null === $token) {
            $payload = json_decode($request->getContent(), true);
            $token = is_array($payload) && isset($payload['_token']) && is_string($payload['_token'])
                ? $payload['_token']
                : (string) $request->request->get('_token', '');
        }

        if (!$this->isCsrfTokenValid('tutorial_complete', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        if (!$user->hasCompletedTutorial()) {
            $user->setHasCompletedTutorial(true);
            $entityManager->flush();
        }

        return $this->json(['completed' => true]);
    }
}
