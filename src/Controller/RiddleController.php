<?php

namespace App\Controller;

use App\Entity\Riddle;
use App\Entity\RiddleGPS;
use App\Entity\RiddleQCM;
use App\Entity\RiddleQRCode;
use App\Entity\RiddleText;
use App\Entity\TreasureHunt;
use App\Form\RiddleGPSType;
use App\Form\RiddleQCMType;
use App\Form\RiddleQRCodeType;
use App\Form\RiddleTextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/riddle')]
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
            $em->persist($riddle);
            $em->flush();

            return $this->redirectToRoute('app_treasure_hunt_edit', ['id' => $hunt->getId()]);
        }

        return $this->render('riddle/new.html.twig', [
            'form' => $form->createView(),
            'hunt' => $hunt,
            'type' => $type,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_riddle_edit', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_treasure_hunt_edit', [
                'id' => $riddle->getTreasureHunt()->getId(),
            ]);
        }

        return $this->render('riddle/edit.html.twig', [
            'form' => $form->createView(),
            'riddle' => $riddle,
        ]);
    }
}
