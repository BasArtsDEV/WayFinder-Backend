<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamFilter;
use App\Form\InviteMemberType;
use App\Form\TeamType;
use App\Repository\NotificationRepository;
use App\Repository\TeamRepository;
use App\Repository\TreasureHuntRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class TeamController extends AbstractController
{
    #[Route('/team', name: 'app_team')]
    public function index(
        TeamRepository $teamRepository,
        PaginatorInterface $paginator,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter] string $q = '',
        #[MapQueryParameter] string $filter = '',
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $currentFilter = TeamFilter::tryFrom($filter) ?? TeamFilter::All;
        $search = mb_substr(trim($q), 0, 50);

        $teams = $paginator->paginate(
            $teamRepository->queryForList($user, $search, $currentFilter),
            $page,
            12,
        );

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
            'q' => $search,
            'currentFilter' => $currentFilter,
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findTeamWithMembers(id)')] ?Team $team,
        TreasureHuntRepository $treasureHuntRepository,
        NotificationRepository $notificationRepository,
    ): Response {
        if (!$team) {
            throw $this->createNotFoundException('L\'équipe demandée n\'existe pas.');
        }

        $treasureHunts = $treasureHuntRepository->findForTeamWithEnrollments($team);

        /** @var User $user */
        $user = $this->getUser();

        $leader = $team->getLeader();

        $isLeader = null !== $leader
            && $leader->getId() === $user->getId();

        $isMember = $isLeader
            || $team->getUsers()->contains($user);

        $hasPendingRequest = false;

        if (!$isLeader && !$isMember) {
            $hasPendingRequest = null !== $notificationRepository->findOneBy([
                'type' => Notification::TYPE_TEAM_JOIN_REQUEST,
                'targetUser' => $user,
                'targetTeam' => $team,
            ]);
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
            'treasurehunts' => $treasureHunts,
            'isLeader' => $isLeader,
            'isMember' => $isMember,
            'hasPendingRequest' => $hasPendingRequest,
        ]);
    }

    #[Route('/team/{id}/banner', name: 'app_team_banner', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function banner(int $id, Request $request, TeamRepository $teamRepository): Response
    {
        $banner = $teamRepository->findBannerById($id);

        if (null === $banner || !preg_match('#^data:(image/(?:jpeg|png|webp));base64,(.+)$#s', $banner, $matches)) {
            throw $this->createNotFoundException();
        }

        $response = new Response();
        $response->setPrivate();
        $response->setMaxAge(300);
        $response->setEtag(md5($banner));

        if ($response->isNotModified($request)) {
            return $response;
        }

        $binary = base64_decode($matches[2], true);
        if (false === $binary) {
            throw $this->createNotFoundException();
        }

        $response->setContent($binary);
        $response->headers->set('Content-Type', $matches[1]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route('/team/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getTeams()->count() >= User::MAX_TEAMS) {
            $this->addFlash('danger', sprintf('Vous ne pouvez pas appartenir à plus de %d équipes.', User::MAX_TEAMS));

            return $this->redirectToRoute('app_team');
        }

        $team = new Team();
        $team->setLeader($user);

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->addUser($user); // le chef est aussi membre (comme dans TeamFactory)
            $em->persist($team);
            $em->flush();

            $this->addFlash('success', 'Votre équipe a bien été créée.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/create.html.twig', ['form' => $form]);
    }

    #[Route('/team/{id}', name: 'app_team_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Team $team,
        EntityManagerInterface $em,
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete'.$team->getId(),
            $request->getPayload()->getString('_token')
        )) {
            foreach ($team->getUsers() as $user) {
                $user->removeTeam($team);
            }

            $em->remove($team);
            $em->flush();
        }

        return $this->redirectToRoute('app_team', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/team/{id}/leave', name: 'app_team_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function leave(
        Request $request,
        Team $team,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // CSRF token verification
        if (!$this->isCsrfTokenValid('leave'.$team->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // The leader cannot leave the team (they must delete it or pass on the role)
        if ($team->getLeader() === $user) {
            $this->addFlash('danger', 'En tant que chef d\'équipe, vous ne pouvez pas quitter l\'équipe. Vous devez la supprimer.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Verify that the user belongs to the team
        if (!$team->getUsers()->contains($user)) {
            $this->addFlash('danger', 'Vous ne faites pas partie de cette équipe.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Remove user
        $team->removeUser($user);
        $em->flush();

        $this->addFlash('success', 'Vous avez quitté l\'équipe avec succès.');

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

    #[Route('/team/{id}/edit', name: 'app_team_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/team/{id}/invite', name: 'app_team_invite', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function invite(
        Team $team,
        Request $request,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $leader */
        $leader = $this->getUser();

        if ($team->getLeader()?->getId() !== $leader->getId()) {
            throw $this->createAccessDeniedException('Seul le chef d\'équipe peut inviter des membres.');
        }

        $form = $this->createForm(InviteMemberType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // "@username" is permitted: this is how usernames are displayed on the site.
            $username = ltrim(trim((string) $form->get('username')->getData()), '@');
            $guest = $userRepository->findOneBy(['username' => $username]);

            $error = $this->getInviteError($team, $guest, $notificationRepository);

            if (null !== $error) {
                $form->get('username')->addError(new FormError($error));
            } else {
                $notification = new Notification();
                $notification->setType(Notification::TYPE_TEAM_INVITATION);
                $notification->setTitle('Invitation à rejoindre une équipe');
                $notification->setDescription(sprintf(
                    '%s vous invite à rejoindre l\'équipe "%s".',
                    $leader->getUsername(),
                    $team->getName(),
                ));
                $notification->setTargetUser($guest);
                $notification->setTargetTeam($team);
                $notification->addReceiveNotif($guest);

                $em->persist($notification);
                $em->flush();

                $this->addFlash('success', sprintf('Invitation envoyée à @%s.', $guest->getUsername()));

                return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
            }
        }

        return $this->render('team/invite.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    /**
     * Returns the reason for the invitation being declined, or null if it is possible.
     */
    private function getInviteError(Team $team, ?User $guest, NotificationRepository $notificationRepository): ?string
    {
        if (null === $guest) {
            return 'Aucun utilisateur ne porte ce nom.';
        }

        if ($team->getLeader()?->getId() === $guest->getId() || $team->getUsers()->contains($guest)) {
            return sprintf('@%s fait déjà partie de l\'équipe.', $guest->getUsername());
        }

        if ($team->getUsers()->count() >= Team::MAX_USERS) {
            return sprintf('Votre équipe est complète (%d membres maximum).', Team::MAX_USERS);
        }

        if ($guest->getTeams()->count() >= User::MAX_TEAMS) {
            return sprintf('@%s a atteint le nombre maximum d\'équipes.', $guest->getUsername());
        }

        // Invitation already pending = notification still present for the guest
        if (null !== $notificationRepository->findOneBy([
            'type' => Notification::TYPE_TEAM_INVITATION,
            'targetUser' => $guest,
            'targetTeam' => $team,
        ])) {
            return sprintf('Une invitation est déjà en attente pour @%s.', $guest->getUsername());
        }

        // The guest has already requested to join, there is no need to invite them.
        if (null !== $notificationRepository->findOneBy([
            'type' => Notification::TYPE_TEAM_JOIN_REQUEST,
            'targetUser' => $guest,
            'targetTeam' => $team,
        ])) {
            return sprintf('@%s a déjà demandé à rejoindre l\'équipe : répondez à sa demande dans vos notifications.', $guest->getUsername());
        }

        return null;
    }
}
