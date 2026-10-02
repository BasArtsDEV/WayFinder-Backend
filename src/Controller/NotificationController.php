<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    /**
     * Display the current user's notifications list.
     */
    #[Route('/notification', name: 'app_notification')]
    public function index(NotificationRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $repository->findByAny($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * A player requests to join a team, creates a notification for the Team Leader.
     */
    #[Route('/team/{id}/request-join', name: 'app_team_request_join', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    public function requestJoinTeam(Team $team, EntityManagerInterface $entityManager): Response
    {
        /** @var User $applicant */
        $applicant = $this->getUser();
        $leader = $team->getLeader();

        if (!$leader) {
            $this->addFlash('danger', 'Cette équipe n\'a pas de chef.');
            return $this->redirectToRoute('app_notification');
        }

        $notification = new Notification();
        $notification->setType(Notification::TYPE_TEAM_JOIN_REQUEST);
        $notification->setTitle('Demande à rejoindre l\'équipe');
        $notification->setDescription(sprintf('%s souhaite rejoindre votre équipe "%s".', $applicant->getUsername(), $team->getName()));
        $notification->setTargetUser($applicant);
        $notification->setTargetTeam($team);
        $notification->addReceiveNotif($leader);

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande pour rejoindre l\'équipe a été envoyée au chef.');

        return $this->redirectToRoute('app_notification');
    }
}
