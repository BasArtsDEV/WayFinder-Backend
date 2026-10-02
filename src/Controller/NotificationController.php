<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    /**
     * The team leader invites a player, create a notification for the target User.
     */
    #[Route('/team/{id}/invite/{userId}', name: 'app_team_invite_user', requirements: ['id' => Requirement::DIGITS, 'userId' => Requirement::DIGITS], methods: ['POST'])]
    public function inviteUserToTeam(Team $team, #[MapEntity(id: 'userId')] User $guest, EntityManagerInterface $entityManager): Response
    {
        /** @var User $leader */
        $leader = $this->getUser();

        if ($team->getLeader() !== $leader) {
            $this->addFlash('danger', 'Seul le chef d\'équipe peut inviter des membres.');

            return $this->redirectToRoute('app_notification');
        }

        $notification = new Notification();
        $notification->setType(Notification::TYPE_TEAM_INVITATION);
        $notification->setTitle('Invitation à rejoindre une équipe');
        $notification->setDescription(sprintf('L\'équipe "%s" vous invite à rejoindre ses rangs !', $team->getName()));
        $notification->setTargetUser($guest);
        $notification->setTargetTeam($team);
        $notification->addReceiveNotif($guest);

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Invitation envoyée à %s.', $guest->getUsername()));

        return $this->redirectToRoute('app_notification');
    }

    /**
     * Process accepting or declining a join request or invitation.
     */
    #[Route('/notification/{id}/action/{action}', name: 'app_notification_action', requirements: ['id' => Requirement::DIGITS, 'action' => 'accept|decline'], methods: ['POST'])]
    public function handleAction(
        Notification $notification,
        string $action,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$notification->getReceiveNotif()->contains($currentUser)) {
            $this->addFlash('danger', 'Accès non autorisé.');

            return $this->redirectToRoute('app_notification');
        }

        $team = $notification->getTargetTeam();

        if ('accept' === $action && $team) {
            if (Notification::TYPE_TEAM_JOIN_REQUEST === $notification->getType()) {
                // The leader accepts the applicant into the team
                $applicant = $notification->getTargetUser();
                if ($applicant) {
                    $team->addUser($applicant);
                    $this->addFlash('success', sprintf('%s a rejoint votre équipe !', $applicant->getUsername()));
                }
            } elseif (Notification::TYPE_TEAM_INVITATION === $notification->getType()) {
                // The player accepts the leader's invitation
                $team->addUser($currentUser);
                $this->addFlash('success', sprintf('Vous avez rejoint l\'équipe "%s" !', $team->getName()));
            }
        } else {
            $this->addFlash('info', 'Demande refusée.');
        }

        $notification->removeReceiveNotif($currentUser);
        if ($notification->getReceiveNotif()->isEmpty()) {
            $entityManager->remove($notification);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_notification');
    }

    /**
     * Delete a notification.
     */
    #[Route('/notification/delete/{notificationId}', name: 'app_notification_delete', requirements: ['notificationId' => Requirement::DIGITS])]
    public function deleteNotification(#[MapEntity(id: 'notificationId')] Notification $notification, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$notification->getReceiveNotif()->contains($user)) {
            return $this->redirectToRoute('app_notification');
        }

        $notification->removeReceiveNotif($user);

        if ($notification->getReceiveNotif()->isEmpty()) {
            $entityManager->remove($notification);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_notification');
    }
}
