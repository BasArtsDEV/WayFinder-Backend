<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TeamController extends AbstractController
{
    #[Route('/team', name: 'app_team')]
    public function index(TeamRepository $teamRepository): Response
    {
        $teams = $teamRepository->findAll();

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/advertisement/new', name: 'app_advertisement_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $team = new Team();

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($team);

            $em->flush();

            return $this->redirectToRoute('app_team');
        }

        return $this->render('team/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
