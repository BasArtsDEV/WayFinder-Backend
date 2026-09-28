<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RiddleController extends AbstractController
{
    #[Route('/riddle', name: 'app_riddle')]
    public function index(): Response
    {
        return $this->render('riddle/index.html.twig', [
            'controller_name' => 'RiddleController',
        ]);
    }
}
