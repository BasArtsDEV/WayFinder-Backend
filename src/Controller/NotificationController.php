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
            $this->addFlash('error', 'Cette équipe n\'a pas de chef.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Check whether a request is already in progress for this team by this user
        $existingRequest = $notificationRepository->findOneBy([
            'type' => Notification::TYPE_TEAM_JOIN_REQUEST,
            'targetUser' => $applicant,
            'targetTeam' => $team,
        ]);

        if ($existingRequest) {
            $this->addFlash('info', 'Vous avez déjà envoyé une demande pour rejoindre cette équipe.');

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
     * Process accepting or declining a join request or invitation,
     * then inform the other party with a simple notification.
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
            $this->addFlash('error', 'Accès non autorisé.');

            return $this->redirectToRoute('app_notification');
        }

        $team = $notification->getTargetTeam();
        $isInvitation = Notification::TYPE_TEAM_INVITATION === $notification->getType();
        $isJoinRequest = Notification::TYPE_TEAM_JOIN_REQUEST === $notification->getType();

        // Invitation: I respond as a guest. Request: I respond as a superior, the candidate is the one making the request.
        $candidate = $isInvitation ? $currentUser : $notification->getTargetUser();

        // Only invitations and requests call for a response.
        if (null === $team || null === $candidate || (!$isInvitation && !$isJoinRequest)) {
            $this->addFlash('error', 'Cette notification n\'appelle aucune réponse.');

            return $this->redirectToRoute('app_notification');
        }

        // The manager may have changed since the request was submitted
        if ($isJoinRequest && $team->getLeader()?->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous n\'êtes plus le chef de cette équipe : cette demande ne vous concerne plus.');
            $this->dismiss($notification, $currentUser, $entityManager);
            $entityManager->flush();

            return $this->redirectToRoute('app_notification');
        }

        // accepted | declined | failed | null (already a member: nothing to report to the other party)
        $outcome = null;
        $failure = null;

        if ('decline' === $action) {
            $outcome = 'declined';
        } elseif ($team->getUsers()->contains($candidate)) {
            $this->addFlash('info', 'Cette personne fait déjà partie de l\'équipe.');
        } elseif ($team->getUsers()->count() >= Team::MAX_USERS) {
            $outcome = 'failed';
            $failure = 'l\'équipe est complète';
        } elseif ($candidate->getTeams()->count() >= User::MAX_TEAMS) {
            $outcome = 'failed';
            $failure = 'le nombre maximum d\'équipes par joueur est atteint';
        } else {
            $team->addUser($candidate);
            $outcome = 'accepted';
        }

        $responder = $currentUser->getUsername();
        $teamName = $team->getName();

        // Message displayed to the person who has just responded
        match ($outcome) {
            'accepted' => $this->addFlash('success', $isInvitation
                ? sprintf('Vous avez rejoint l\'équipe "%s" !', $teamName)
                : sprintf('%s a rejoint votre équipe !', $candidate->getUsername())),
            'declined' => $this->addFlash('success', $isInvitation ? 'Invitation refusée.' : 'Demande refusée.'),
            'failed' => $this->addFlash('error', sprintf('Impossible : %s.', $failure)),
            default => null,
        };

        // Informative notification for the other party: the manager (invitation) or the requester (request)
        $otherParty = $isInvitation ? $team->getLeader() : $candidate;

        if (null !== $outcome && null !== $otherParty && $otherParty->getId() !== $currentUser->getId()) {
            [$title, $description] = match (true) {
                $isInvitation && 'accepted' === $outcome => [
                    'Invitation acceptée',
                    sprintf('%s a accepté votre invitation et a rejoint l\'équipe "%s".', $responder, $teamName),
                ],
                $isInvitation && 'declined' === $outcome => [
                    'Invitation refusée',
                    sprintf('%s a refusé votre invitation à rejoindre l\'équipe "%s".', $responder, $teamName),
                ],
                $isInvitation => [
                    'Invitation sans suite',
                    sprintf('%s n\'a pas pu rejoindre l\'équipe "%s" : %s.', $responder, $teamName, $failure),
                ],
                'accepted' === $outcome => [
                    'Demande acceptée',
                    sprintf('%s a accepté votre demande : vous avez rejoint l\'équipe "%s" !', $responder, $teamName),
                ],
                'declined' === $outcome => [
                    'Demande refusée',
                    sprintf('%s a refusé votre demande pour rejoindre l\'équipe "%s".', $responder, $teamName),
                ],
                default => [
                    'Demande sans suite',
                    sprintf('%s a accepté votre demande pour rejoindre l\'équipe "%s", mais vous n\'avez pas pu la rejoindre : %s.', $responder, $teamName, $failure),
                ],
            };

            $entityManager->persist(
                new Notification()
                    ->setType('accepted' === $outcome ? Notification::TYPE_TEAM_RESPONSE_ACCEPTED : Notification::TYPE_TEAM_RESPONSE_DECLINED)
                    ->setTitle($title)
                    ->setDescription($description)
                    ->setTargetUser($candidate)
                    ->setTargetTeam($team)
                    ->addReceiveNotif($otherParty)
            );
        }

        $this->dismiss($notification, $currentUser, $entityManager);
        $entityManager->flush();

        return $this->redirectToRoute('app_notification');
    }

    /**
     * Remove the notification for this user, and delete it if nobody else still has it.
     */
    private function dismiss(Notification $notification, User $user, EntityManagerInterface $entityManager): void
    {
        $notification->removeReceiveNotif($user);

        if ($notification->getReceiveNotif()->isEmpty()) {
            $entityManager->remove($notification);
        }
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
        $this->addFlash('success', 'Toutes les notifications ont été supprimées.');

        return $this->redirectToRoute('app_notification');
    }
}
