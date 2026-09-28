<?php

namespace App\Controller;

use App\Entity\TreasureHunt;
use App\Form\TreasureHuntType;
use App\Repository\TreasureHuntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/treasure_hunt/{id}', name: 'app_treasure_hunt_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[MapEntity(expr: 'repository.find(id)')] TreasureHunt $treasureHunt): Response
    {
        $riddles = $treasureHunt->getRiddles();

        return $this->render('treasure_hunt/show.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => $riddles,
        ]);
    }

    #[Route('/treasure_hunt/new', name: 'app_treasure_hunt_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $treasureHunt = new TreasureHunt();

        // Création du formulaire et ajout du bouton submit
        $form = $this->createForm(TreasureHuntType::class, $treasureHunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($treasureHunt);

            // Enregistrer les données
            $em->flush();

            // Redirection après succès
            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
        }

        return $this->render('treasure_hunt/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/treasure_hunt/{id}/edit', name: 'app_treasure_hunt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em): Response
    {
        // Création du formulaire et ajout du bouton submit
        $form = $this->createForm(TreasureHuntType::class, $treasureHunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer les données
            $em->flush();

            // Redirection après succès
            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
        }

        return $this->render('treasure_hunt/edit.html.twig', [
            'form' => $form->createView(),
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
}
