<?php

namespace App\State;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\GameRiddle;
use App\ApiResource\GameState;
use App\Entity\Enrollment;
use App\Entity\Riddle;
use App\Entity\RiddleGPS;
use App\Entity\RiddleQCM;
use App\Entity\RiddleQRCode;
use App\Entity\RiddleText;
use App\Entity\User;
use App\Enum\EnrollmentStatus;
use App\Repository\EnrollmentRepository;
use App\Service\GameProgressionService;
use App\Service\GameScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<GameState>
 */
readonly class GameStateProvider implements ProviderInterface
{
    public function __construct(
        private EnrollmentRepository $enrollmentRepository,
        private EntityManagerInterface $em,
        private Security $security,
        private GameProgressionService $gameProgressionService,
        private GameScoreService $gameScoreService,
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GameState
    {
        $enrollment = $this->enrollmentRepository->find($uriVariables['id'] ?? 0);
        if (null === $enrollment) {
            throw new NotFoundHttpException('Cette partie n\'existe pas.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $enrollment->getUser() !== $user) {
            throw new AccessDeniedHttpException('Vous ne pouvez consulter que vos propres parties.');
        }

        if (!$enrollment->getTreasureHunt()?->isPublished()) {
            throw new NotFoundHttpException('Cette chasse au trésor n\'est pas disponible.');
        }

        $this->gameProgressionService->updateTimers($enrollment);
        $this->em->flush();

        return $this->createGameState($enrollment);
    }

    private function createGameState(Enrollment $enrollment): GameState
    {
        $treasureHunt = $enrollment->getTreasureHunt();
        $riddles = $treasureHunt->getRiddles()->toArray();
        usort($riddles, static fn (Riddle $a, Riddle $b): int => $a->getIndex() <=> $b->getIndex());

        $state = new GameState();
        $state->id = (int) $enrollment->getId();
        $state->enrollment = $this->iriConverter->getIriFromResource($enrollment);
        $state->status = $enrollment->getStatus()->value;
        $state->timedOut = $this->gameProgressionService->isTimedOut($enrollment);
        $state->treasureHuntId = (int) $treasureHunt->getId();
        $state->treasureHuntTitle = (string) $treasureHunt->getTitle();
        $state->teamName = $enrollment->getTeam()?->getName();
        $state->totalSteps = count($riddles);
        $state->duration = $treasureHunt->getDuration();
        $state->elapsedTime = $enrollment->getCountdown();
        $state->remainingTime = $this->gameProgressionService->getRemainingTime($enrollment);
        $state->score = $this->gameScoreService->getScore($enrollment);
        $state->rank = $this->gameScoreService->getRank($enrollment);
        $state->competitorsCount = $this->gameScoreService->getCompetitorsCount($enrollment);

        $riddle = $enrollment->getRiddle();
        $position = null === $riddle ? false : array_search($riddle, $riddles, true);
        $state->step = false === $position ? $this->getDefaultStep($enrollment, $state->totalSteps) : $position + 1;

        if (null !== $riddle && EnrollmentStatus::FINISHED !== $enrollment->getStatus()) {
            $state->riddle = $this->createGameRiddle($riddle);
        }

        return $state;
    }

    private function getDefaultStep(Enrollment $enrollment, int $totalSteps): int
    {
        return EnrollmentStatus::FINISHED === $enrollment->getStatus() ? $totalSteps : 0;
    }

    private function createGameRiddle(Riddle $riddle): GameRiddle
    {
        $gameRiddle = new GameRiddle();
        $gameRiddle->iri = $this->iriConverter->getIriFromResource($riddle);
        $gameRiddle->title = (string) $riddle->getTitle();
        $gameRiddle->description = (string) $riddle->getDescription();
        $gameRiddle->imgDescription = $riddle->getImgDescription();
        $gameRiddle->secondsPenalty = $riddle->getSecondsPenalty();

        $image = $riddle->getImgBase64();
        $gameRiddle->image = null !== $image ? 'data:image/png;base64,'.$image : null;

        $gameRiddle->type = match (true) {
            $riddle instanceof RiddleQCM => GameRiddle::TYPE_QCM,
            $riddle instanceof RiddleGPS => GameRiddle::TYPE_GPS,
            $riddle instanceof RiddleQRCode => GameRiddle::TYPE_QRCODE,
            default => GameRiddle::TYPE_TEXT,
        };

        if ($riddle instanceof RiddleText) {
            $gameRiddle->question = $riddle->getQuestion();
        }

        if ($riddle instanceof RiddleQCM) {
            $gameRiddle->question = $riddle->getQuestion();

            foreach ($riddle->getChoices() as $choice) {
                $gameRiddle->choices[] = ['id' => (int) $choice->getId(), 'label' => (string) $choice->getChoice()];
                $gameRiddle->answersCount += $choice->isRight() ? 1 : 0;
            }
        }

        return $gameRiddle;
    }
}
