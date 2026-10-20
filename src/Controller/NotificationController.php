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
    #[Route('/team/{id}/request-join', name: 'app_team_request_join', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function requestJoinTeam(Team $team, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        /** @var User $applicant */
        $applicant = $this->getUser();
        $leader = $team->getLeader();

        if (!$leader) {
            $this->addFlash('danger', 'Cette équipe n\'a pas de chef.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Check whether a request is already in progress for this team by this user
        $existingRequest = $notificationRepository->findOneBy([
            'type' => Notification::TYPE_TEAM_JOIN_REQUEST,
            'targetUser' => $applicant,
            'targetTeam' => $team,
        ]);

        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà envoyé une demande pour rejoindre cette équipe.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
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

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
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
            $isInvitation = Notification::TYPE_TEAM_INVITATION === $notification->getType();

            // Invitation: the person invited joins. Request: the requester joins.
            $newMember = $isInvitation ? $currentUser : $notification->getTargetUser();

            if ($newMember) {
                if ($team->getUsers()->contains($newMember)) {
                    $this->addFlash('info', 'Cette personne fait déjà partie de l\'équipe.');
                } elseif ($team->getUsers()->count() >= Team::MAX_USERS) {
                    $this->addFlash('danger', 'Impossible : l\'équipe est complète.');
                } elseif ($newMember->getTeams()->count() >= User::MAX_TEAMS) {
                    $this->addFlash('danger', sprintf('Impossible : %s a atteint le nombre maximum d\'équipes.', $isInvitation ? 'vous avez' : $newMember->getUsername().' a'));
                } else {
                    $team->addUser($newMember);
                    $this->addFlash('success', $isInvitation
                        ? sprintf('Vous avez rejoint l\'équipe "%s" !', $team->getName())
                        : sprintf('%s a rejoint votre équipe !', $newMember->getUsername()));
                }
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

    /**
     * Clear all notifications for the current user.
     */
    #[Route('/notification/delete-all', name: 'app_notification_delete_all')]
    public function deleteAllNotifications(NotificationRepository $repository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $repository->findByAny($user);

        foreach ($notifications as $notification) {
            $notification->removeReceiveNotif($user);

            if ($notification->getReceiveNotif()->isEmpty()) {
                $entityManager->remove($notification);
            }
        }

        $entityManager->flush();
        $this->addFlash('info', 'Toutes les notifications ont été supprimées.');

        return $this->redirectToRoute('app_notification');
    }
}
