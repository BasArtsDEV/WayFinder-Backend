<?php

namespace App\Controller;

use App\Entity\Riddle;
use App\Entity\RiddleGPS;
use App\Entity\RiddleQCM;
use App\Entity\RiddleQRCode;
use App\Entity\RiddleText;
use App\Entity\TreasureHunt;
use App\Form\Riddle\RiddleGPSType;
use App\Form\Riddle\RiddleQCMType;
use App\Form\Riddle\RiddleQRCodeType;
use App\Form\Riddle\RiddleTextType;
use App\Repository\RiddleRepository;
use App\Security\Voter\RiddleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class RiddleController extends AbstractController
{
    #[Route('/treasure-hunt/{huntId}/new/{type}', name: 'app_riddle_new', methods: ['GET', 'POST'])]
    public function new(
        int $huntId,
        string $type,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $hunt = $em->getRepository(TreasureHunt::class)->find($huntId);
        if (!$hunt) {
            throw $this->createNotFoundException('Chasse introuvable.');
        }

        $riddle = match ($type) {
            'text' => new RiddleText(),
            'qcm' => new RiddleQCM(),
            'gps' => new RiddleGPS(),
            'qrcode' => new RiddleQRCode(),
            default => throw $this->createNotFoundException('Type d\'énigme inconnu'),
        };

        $riddle->setTreasureHunt($hunt);
        $riddle->setIndex(count($hunt->getRiddles()) + 1);

        $formType = match ($type) {
            'qcm' => RiddleQCMType::class,
            'gps' => RiddleGPSType::class,
            'qrcode' => RiddleQRCodeType::class,
            default => RiddleTextType::class,
        };

        $form = $this->createForm($formType, $riddle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imgFile = $form->get('imgFile')->getData();

            if ($imgFile) {
                $binaryData = file_get_contents($imgFile->getPathname());
                $riddle->setImg($binaryData);
            }

            $em->persist($riddle);
            $em->flush();

            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $hunt->getId()]);
        }

        return $this->render('riddle/new.html.twig', [
            'form' => $form->createView(),
            'hunt' => $hunt,
            'type' => $type,
        ]);
    }

    #[Route('riddle/{id}/edit', name: 'app_riddle_edit', methods: ['GET', 'POST'])]
    #[IsGranted(RiddleVoter::EDIT, 'riddle')]
    public function edit(
        Riddle $riddle,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $formType = match (get_class($riddle)) {
            RiddleQCM::class => RiddleQCMType::class,
            RiddleGPS::class => RiddleGPSType::class,
            RiddleQRCode::class => RiddleQRCodeType::class,
            default => RiddleTextType::class,
        };

        $form = $this->createForm($formType, $riddle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_treasure_hunt_show', [
                'id' => $riddle->getTreasureHunt()->getId(),
            ]);
        }

        return $this->render('riddle/edit.html.twig', [
            'form' => $form->createView(),
            'riddle' => $riddle,
        ]);
    }

    #[Route('riddle/{id}/delete', name: 'app_riddle_delete', methods: ['POST'])]
    #[IsGranted(RiddleVoter::DELETE, 'riddle')]
    public function delete(
        Request $request,
        Riddle $riddle,
        EntityManagerInterface $em,
    ): Response {
        $huntId = $riddle->getTreasureHunt()->getId();

        if ($this->isCsrfTokenValid('delete'.$riddle->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($riddle);
            $em->flush();
        }

        return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $huntId]);
    }

    #[Route('/riddle/reorder-all', name: 'app_riddle_reorder_all', methods: ['POST'])]
    public function reorderAll(
        Request $request,
        RiddleRepository $riddleRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['orders']) || !is_array($data['orders'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        foreach ($data['orders'] as $item) {
            $riddle = $riddleRepository->find($item['id']);
            if ($riddle) {
                $riddle->setIndex((int) $item['index']);
            }
        }

        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }
}
