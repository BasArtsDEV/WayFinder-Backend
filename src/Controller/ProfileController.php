<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    public function avatar(User $user): Response
    {
        if (!$user->getAvatar()) {
            throw $this->createNotFoundException('Pas d\'avatar');
        }

        $avatarData = is_resource($user->getAvatar())
            ? stream_get_contents($user->getAvatar())
            : $user->getAvatar();

        return new Response($avatarData, 200, [
            'Content-Type' => 'image/jpeg',
        ]);
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
}
