<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Submission;
use App\Entity\User;
use App\Enum\EnrollmentStatus;
use App\Service\AnswerValidatorService;
use App\Service\GameProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<Submission, Submission>
 */
readonly class SubmissionProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private AnswerValidatorService $answerValidatorService,
        private GameProgressionService $gameProgressionService,
    ) {
    }

    /**
     * @param Submission $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Submission
    {
        $user = $this->security->getUser();
        $enrollment = $data->getEnrollment();
        $riddle = $data->getRiddle();

        if (!$user instanceof User || null === $enrollment || $enrollment->getUser() !== $user) {
            throw new AccessDeniedHttpException('Vous ne pouvez répondre que pour votre propre inscription.');
        }

        if (null === $riddle) {
            throw new UnprocessableEntityHttpException('L\'énigme est obligatoire.');
        }

        if (!$enrollment->getTreasureHunt()?->isPublished()) {
            throw new UnprocessableEntityHttpException('Cette chasse au trésor n\'est pas publiée.');
        }

        if (EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus()) {
            throw new ConflictHttpException('La partie doit être en cours pour envoyer une réponse.');
        }

        if ($riddle->getTreasureHunt() !== $enrollment->getTreasureHunt() || $enrollment->getRiddle() !== $riddle) {
            throw new ConflictHttpException('Vous devez répondre à l\'énigme courante de cette chasse.');
        }

        $this->gameProgressionService->updateTimers($enrollment);
        if (EnrollmentStatus::IN_PROGRESS !== $enrollment->getStatus()) {
            $this->em->flush();

            throw new ConflictHttpException('Le temps imparti est écoulé.');
        }

        $isRight = $this->answerValidatorService->isCorrect($riddle, $data->getSubmittedValue());
        $data->setIsRight($isRight)->setSubmissionDate(new \DateTimeImmutable());
        $data->setAppliedPenalty($isRight ? 0 : max(0, $riddle->getSecondsPenalty()));
        $data->setResolutionTime($this->gameProgressionService->applyAnswer($enrollment, $isRight));

        $enrollment->addSubmission($data);
        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
