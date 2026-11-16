<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\TreasureHunt;
use App\Repository\TreasureHuntRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<TreasureHunt>
 */
readonly class TreasureHuntByCodeProvider implements ProviderInterface
{
    public function __construct(
        private TreasureHuntRepository $treasureHuntRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TreasureHunt
    {
        $code = strtoupper(trim((string) ($uriVariables['invitationCode'] ?? '')));

        $treasureHunt = $this->treasureHuntRepository->findOneBy([
            'invitationCode' => $code,
            'published' => true,
        ]);

        if (null === $treasureHunt) {
            throw new NotFoundHttpException('Aucune chasse au trésor ne correspond à ce code.');
        }

        return $treasureHunt;
    }
}
