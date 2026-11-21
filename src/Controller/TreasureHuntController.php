<?php

namespace App\Controller;

use App\Entity\RiddleGPS;
use App\Entity\RiddleQCM;
use App\Entity\RiddleQRCode;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Form\TreasureHuntType;
use App\Repository\EnrollmentRepository;
use App\Repository\RiddleQCMRepository;
use App\Repository\RiddleRepository;
use App\Repository\SubmissionRepository;
use App\Repository\TreasureHuntRepository;
use App\Security\Voter\TreasureHuntVoter;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Knp\Component\Pager\PaginatorInterface;
use Random\RandomException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $treasureHunts = $treasureHuntRepository->findByCreatorUser($user);

        return $this->render('treasure_hunt/index.html.twig', [
            'treasureHunts' => $treasureHunts,
        ]);
    }

    #[Route('/treasure_hunt/new', name: 'app_treasure_hunt_new', methods: ['GET', 'POST'])]
    #[IsGranted(TreasureHuntVoter::ADD)]
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
    public function show(
        #[MapEntity(expr: 'repository.findByTeam(id)')] TreasureHunt $treasureHunt,
        EnrollmentRepository $enrollmentRepository,
        RiddleQCMRepository $riddleQCMRepository,
        PaginatorInterface $paginator,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
    ): Response {
        $feedbacks = $paginator->paginate(
            $enrollmentRepository->queryFeedbacksOrderedByDate($treasureHunt),
            $page,
            options: ['distinct' => false],
        );
        $riddles = $treasureHunt->getRiddles();

        $map = new Map()->zoom(13);
        $hasGpsRiddles = false;
        $hasQcmRiddles = false;

        foreach ($riddles as $riddle) {
            if ($riddle instanceof RiddleQCM) {
                $hasQcmRiddles = true;
            } elseif ($riddle instanceof RiddleGPS) {
                $hasGpsRiddles = true;
                $point = new Point($riddle->getLatitude(), $riddle->getLongitude());

                $map->center($point);

                $map->addMarker(new Marker(
                    position: $point,
                    title: $riddle->getLocationName() ?? "Lieu de l'énigme"
                ));
            }
        }

        if ($hasQcmRiddles) {
            $riddleQCMRepository->preloadChoicesForTreasureHunt($treasureHunt);
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
            'feedbacks' => $feedbacks,
            'map' => $hasGpsRiddles ? $map : null,
        ]);
    }

    #[Route('/treasure_hunt/{id}/qrcodes', name: 'app_treasure_hunt_qrcodes', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function printQrCodes(
        #[MapEntity(expr: 'repository.findByTeam(id)')] TreasureHunt $treasureHunt,
    ): Response {
        $qrCodeRiddles = [];
        foreach ($treasureHunt->getRiddles() as $riddle) {
            if ($riddle instanceof RiddleQRCode) {
                $qrCodeRiddles[] = $riddle;
            }
        }

        if (empty($qrCodeRiddles)) {
            $this->addFlash('error', 'Cette chasse ne contient aucune énigme QR Code.');

            return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
        }

        $html = $this->renderView('treasure_hunt/qrcodes_pdf.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => $qrCodeRiddles,
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="qrcodes-chasse-'.$treasureHunt->getId().'.pdf"',
        ]);
    }

    #[Route('/treasure_hunt/{id}/edit', name: 'app_treasure_hunt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted(TreasureHuntVoter::EDIT, 'treasureHunt')]
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
    #[IsGranted(TreasureHuntVoter::DELETE, 'treasureHunt')]
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
    public function publish(Request $request, TreasureHunt $treasureHunt, RiddleRepository $riddleRepository, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('publish'.$treasureHunt->getId(), $request->getPayload()->getString('_token'))) {
            if (0 === $riddleRepository->count(['treasureHunt' => $treasureHunt])) {
                $this->addFlash('error', 'Impossible de publier une chasse sans énigmes.');

                return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
            }

            $treasureHunt->setPublished(true);
            if (null === $treasureHunt->getInvitationCode()) {
                $treasureHunt->generateInvitationCode();
            }

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

            $this->addFlash('success', 'La chasse au trésor n\'est plus publiée.');
        }

        return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
    }

    #[Route('/treasure_hunt/{id}/reset-scores', name: 'app_treasure_hunt_reset_scores', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(TreasureHuntVoter::EDIT, 'treasureHunt', message: 'Seuls les membres de l\'équipe peuvent effacer les scores.')]
    public function resetScores(
        Request $request,
        TreasureHunt $treasureHunt,
        EntityManagerInterface $em,
        SubmissionRepository $submissionRepository,
        EnrollmentRepository $enrollmentRepository,
    ): Response {
        if ($this->isCsrfTokenValid('reset-scores'.$treasureHunt->getId(), $request->getPayload()->getString('_token'))) {
            $em->wrapInTransaction(static function () use ($treasureHunt, $submissionRepository, $enrollmentRepository): void {
                $submissionRepository->deleteByTreasureHunt($treasureHunt);
                $enrollmentRepository->resetByTreasureHunt($treasureHunt);
            });

            $this->addFlash('success', 'Les scores et le classement de la chasse au trésor ont été effacés.');
        }

        return $this->redirectToRoute('app_treasure_hunt_show', ['id' => $treasureHunt->getId()]);
    }
}
