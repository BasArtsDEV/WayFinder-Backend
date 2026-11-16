<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Enrollment;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Service\GameProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<Enrollment, Enrollment>
 */
readonly class EnrollmentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private EnrollmentRepository $enrollmentRepository,
        private Security $security,
        private GameProgressionService $gameProgressionService,
    ) {
    }

    /**
     * @param Enrollment $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Enrollment
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $treasureHunt = $data->getTreasureHunt();
        if (null === $treasureHunt) {
            throw new UnprocessableEntityHttpException('La chasse au trésor est obligatoire.');
        }

        if (!$operation instanceof Post) {
            if ($data->getUser() !== $user) {
                throw new AccessDeniedHttpException();
            }

            $previous = $context['previous_data'] ?? null;
            $target = null;
            if ($previous instanceof Enrollment && $data->getStatus() !== $previous->getStatus()) {
                $target = $data->getStatus();
                $data->setStatus($previous->getStatus());
            }

            $this->gameProgressionService->transition($data, $target);
            $this->em->flush();

            return $data;
        }

        $team = $data->getTeam();
        if (null !== $team && !$team->getUsers()->contains($user)) {
            throw new AccessDeniedHttpException('Vous ne faites pas partie de cette équipe.');
        }

        if (null !== $team && Team::ROLE_PARTICIPANT !== $team->getRole()) {
            throw new UnprocessableEntityHttpException('Seules les équipes participantes peuvent s\'inscrire à une chasse.');
        }

        return $this->em->wrapInTransaction(function () use ($data, $user, $treasureHunt, $team): Enrollment {
            $existing = $this->enrollmentRepository->findOneBy(['user' => $user, 'treasureHunt' => $treasureHunt]);
            if (null !== $existing) {
                if ($existing->getTeam() !== $team) {
                    throw new ConflictHttpException('Vous êtes déjà inscrit à cette chasse dans un autre mode de jeu.');
                }

                return $existing;
            }

            if (!$treasureHunt->isPublished()) {
                throw new UnprocessableEntityHttpException('Cette chasse au trésor n\'est pas publiée.');
            }

            if (null === $team) {
                $data->setUser($user);
                $this->em->persist($data);

                return $data;
            }

            if (null !== $this->enrollmentRepository->findOneBy(['team' => $team, 'treasureHunt' => $treasureHunt])) {
                throw new ConflictHttpException('Cette équipe a déjà commencé cette chasse.');
            }

            foreach ($team->getUsers() as $member) {
                if (null !== $this->enrollmentRepository->findOneBy(['user' => $member, 'treasureHunt' => $treasureHunt])) {
                    throw new ConflictHttpException(sprintf('%s est déjà inscrit à cette chasse.', $member->getUsername()));
                }
            }

            foreach ($team->getUsers() as $member) {
                $enrollment = $member === $user ? $data : new Enrollment();
                $enrollment->setUser($member)->setTreasureHunt($treasureHunt);
                $team->addEnrollment($enrollment);
                $this->em->persist($enrollment);
            }

            return $data;
        });
    }
}
