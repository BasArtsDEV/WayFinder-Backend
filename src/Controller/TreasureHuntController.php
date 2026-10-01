<?php

namespace App\Controller;

use App\Entity\RiddleGPS;
use App\Entity\TreasureHunt;
use App\Form\TreasureHuntType;
use App\Repository\TreasureHuntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class TreasureHuntController extends AbstractController
{
    #[Route('/treasure_hunt', name: 'app_treasure_hunt')]
    public function index(TreasureHuntRepository $treasureHuntRepository): Response
    {
        $treasureHunts = $treasureHuntRepository->findAllOrderedByDate();

        return $this->render('treasure_hunt/index.html.twig', [
            'treasureHunts' => $treasureHunts,
        ]);
    }

    #[Route('/treasure_hunt/new', name: 'app_treasure_hunt_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $treasureHunt = new TreasureHunt();

        $form = $this->createForm(TreasureHuntType::class, $treasureHunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($treasureHunt);
            $em->flush();

            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
        }

        return $this->render('treasure_hunt/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/treasure_hunt/{id}', name: 'app_treasure_hunt_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[MapEntity(expr: 'repository.find(id)')] TreasureHunt $treasureHunt): Response
    {
        $riddles = $treasureHunt->getRiddles();

        $map = new Map()->zoom(13);
        $hasGpsRiddles = false;

        foreach ($riddles as $riddle) {
            if ($riddle instanceof RiddleGPS) {
                $hasGpsRiddles = true;
                $point = new Point($riddle->getLatitude(), $riddle->getLongitude());

                $map->center($point);

                $map->addMarker(new Marker(
                    position: $point,
                    title: $riddle->getLocationName() ?? "Lieu de l'énigme"
                ));
            }
        }

        if ($hasGpsRiddles) {
            $map->options(
                new GoogleOptions()
                ->mapId('692866589361')
            );
        }

        return $this->render('treasure_hunt/show.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => $riddles,
            'map' => $hasGpsRiddles ? $map : null,
        ]);
    }

    #[Route('/treasure_hunt/{id}/edit', name: 'app_treasure_hunt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TreasureHuntType::class, $treasureHunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
        }

        return $this->render('treasure_hunt/edit.html.twig', [
            'form' => $form,
            'treasureHunt' => $treasureHunt,
        ]);
    }

    #[Route('/treasure_hunt/{id}', name: 'app_treasure_hunt_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$treasureHunt->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($treasureHunt);
            $em->flush();
        }

        return $this->redirectToRoute('app_treasure_hunt', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @throws RandomException
     */
    #[Route('/treasure_hunt/{id}/publish', name: 'app_treasure_hunt_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('publish'.$treasureHunt->getId(), $request->getPayload()->getString('_token'))) {

            if (count($treasureHunt->getRiddles()) === 0) {
                $this->addFlash('error', 'Impossible de publier une chasse sans énigmes.');
                return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
            }

            $treasureHunt->setPublished(true);
            $treasureHunt->generateInvitationCode();

            $em->flush();

            $this->addFlash('success', 'La chasse au trésor a été publiée avec succès.');
        }

        return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
    }

    #[Route('/treasure_hunt/{id}/unpublish', name: 'app_treasure_hunt_unpublish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unpublish(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('unpublish'.$treasureHunt->getId(), $request->getPayload()->getString('_token'))) {
            $treasureHunt->setPublished(false);
            $em->flush();

            $this->addFlash('warning', 'La chasse au trésor n\'est plus publiée.');
        }

        return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
    }
}
