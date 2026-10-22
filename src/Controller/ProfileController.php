<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AvatarService;
use App\Service\UserDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfilController',
        ]);
    }

    #[Route('/user/{id}/avatar', name: 'app_user_avatar')]
    public function avatar(User $user, AvatarService $avatarService): Response
    {
        return $avatarService->getAvatarResponse($user);
    }

    #[Route('/profil/edit', name: 'app_profil_edit', methods: ['POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setUsername($request->request->get('username'));
        $user->setFirstname($request->request->get('firstName'));
        $user->setLastname($request->request->get('lastName'));

        /** @var UploadedFile $avatarFile */
        $avatarFile = $request->files->get('avatar');

        /** @var UploadedFile $bannerFile */
        $bannerFile = $request->files->get('banner');

        if (null != $avatarFile) {
            $binaryDataAvatar = file_get_contents($avatarFile->getPathname());
            $user->setAvatar($binaryDataAvatar);
        }
        if (null != $bannerFile) {
            $binaryDataBanner = file_get_contents($bannerFile->getPathname());
            $mimeType = $bannerFile->getMimeType();
            $user->setBanner('data:'.$mimeType.';base64,'.base64_encode($binaryDataBanner));
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/avatar/delete', name: 'app_profil_avatar_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteAvatar(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setAvatar(null);
        $em->flush();

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/profil/user/delete', name: 'app_user_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request,
        UserDeletionService $userDeletion,
        TokenStorageInterface $tokenStorage,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete-account-'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, la suppression a été annulée.');

            return $this->redirectToRoute('app_profil_edit');
        }

        $userDeletion->delete($user);

        $tokenStorage->setToken(null);
        $session = $request->getSession();
        $session->invalidate();
        $this->addFlash('success', 'Votre compte a bien été supprimé.');

        return $this->redirectToRoute('app_login');
    }
}
