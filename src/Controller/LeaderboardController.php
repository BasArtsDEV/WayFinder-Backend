<?php

namespace App\Controller;

use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\SubmissionRepository;
use App\Security\Voter\TreasureHuntVoter;
use App\Service\GameProgressionService;
use App\Service\GameScoreService;
use App\Service\HuntStatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class LeaderboardController extends AbstractController
{
    #[Route('/treasure_hunt/{id}/leaderboard', name: 'app_treasure_hunt_leaderboard', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(TreasureHunt $treasureHunt, HuntStatisticsService $huntStatisticsService): Response
    {
        $this->denyAccessUnlessCreator($treasureHunt);

        $rows = $huntStatisticsService->getDetailedLeaderboard($treasureHunt);

        return $this->render('leaderboard/index.html.twig', [
            'treasureHunt' => $treasureHunt,
            'rows' => $rows,
            'summary' => $huntStatisticsService->getSummary($treasureHunt, $rows),
            'riddleStatistics' => $huntStatisticsService->getRiddleStatistics($treasureHunt),
            'ongoingGames' => $huntStatisticsService->getOngoingGames($treasureHunt),
        ]);
    }

    #[Route('/treasure_hunt/{id}/leaderboard/export', name: 'app_treasure_hunt_leaderboard_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(TreasureHunt $treasureHunt, HuntStatisticsService $huntStatisticsService): Response
    {
        $this->denyAccessUnlessCreator($treasureHunt);

        $file = fopen('php://temp', 'r+');
        if (false === $file) {
            throw new \RuntimeException("Impossible de générer l'export du classement.");
        }

        fputcsv($file, ['Rang', 'Participant', 'Type', 'Joueurs', 'Score', 'Points', 'Bonus temps', 'Temps de jeu (s)', 'Temps écoulé', 'Fin de partie'], ';', '"', '');
        foreach ($huntStatisticsService->getDetailedLeaderboard($treasureHunt) as $row) {
            $entry = $row['entry'];
            fputcsv($file, [
                $entry['rank'],
                $entry['team']?->getName() ?? $entry['user']?->getUsername(),
                null !== $entry['team'] ? 'Équipe' : 'Solo',
                $row['players'],
                $entry['score'],
                $row['points'],
                $row['bonus'],
                $row['playTime'],
                $entry['timedOut'] ? 'Oui' : 'Non',
                $row['finishedAt']?->format('d/m/Y H:i'),
            ], ';', '"', '');
        }

        rewind($file);
        $csv = (string) stream_get_contents($file);
        fclose($file);

        return new Response("\u{FEFF}".$csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="classement-chasse-'.$treasureHunt->getId().'.csv"',
        ]);
    }

    #[Route('/treasure_hunt/{id}/reset-scores', name: 'app_treasure_hunt_reset_scores', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(TreasureHuntVoter::RESET, 'treasureHunt')]
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

        return $this->redirectToRoute('app_treasure_hunt_leaderboard', ['id' => $treasureHunt->getId()]);
    }

    #[Route('/treasure_hunt/{id}/leaderboard/remove', name: 'app_treasure_hunt_leaderboard_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeEntries(Request $request, TreasureHunt $treasureHunt, EntityManagerInterface $em, GameScoreService $gameScoreService, GameProgressionService $gameProgressionService): Response
    {
        $this->denyAccessUnlessCreator($treasureHunt);

        $payload = $request->getPayload();
        if (!$this->isCsrfTokenValid('remove-entries'.$treasureHunt->getId(), $payload->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire a expiré, veuillez réessayer.');

            return $this->redirectToRoute('app_treasure_hunt_leaderboard', ['id' => $treasureHunt->getId()]);
        }

        $key = $payload->getString('key');
        $keys = '' !== $key ? [$key] : $payload->all('keys');

        $removed = [];
        foreach ($treasureHunt->getEnrollments() as $enrollment) {
            if (null === $enrollment->getUser()) {
                continue;
            }

            $competitorKey = $gameScoreService->getCompetitorKey($enrollment);
            if (in_array($competitorKey, $keys, true)) {
                $gameProgressionService->reset($enrollment);
                $removed[$competitorKey] = true;
            }
        }

        if ([] === $removed) {
            $this->addFlash('info', 'Aucune entrée sélectionnée.');
        } else {
            $em->flush();
            $this->addFlash('success', 1 === count($removed) ? "L'entrée a été retirée du classement." : count($removed).' entrées ont été retirées du classement.');
        }

        return $this->redirectToRoute('app_treasure_hunt_leaderboard', ['id' => $treasureHunt->getId()]);
    }

    private function denyAccessUnlessCreator(TreasureHunt $treasureHunt): void
    {
        $user = $this->getUser();
        $team = $treasureHunt->getTeam();
        if (!$user instanceof User || null === $team || ($team->getLeader() !== $user && !$team->getUsers()->contains($user))) {
            throw $this->createAccessDeniedException("Seuls les membres de l'équipe créatrice peuvent consulter le classement.");
        }
    }
}
