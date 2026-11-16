<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use App\Entity\TreasureHunt;
use Doctrine\ORM\QueryBuilder;

/**
 * Restricts the public treasure hunts collection to published and listed hunts.
 */
final readonly class ListedTreasureHuntExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (TreasureHunt::class !== $resourceClass || !$operation instanceof HttpOperation || !empty($operation->getUriVariables())) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $published = $queryNameGenerator->generateParameterName('published');
        $listed = $queryNameGenerator->generateParameterName('listed');

        $queryBuilder
            ->andWhere(sprintf('%s.published = :%s', $rootAlias, $published))
            ->andWhere(sprintf('%s.listed = :%s', $rootAlias, $listed))
            ->setParameter($published, true)
            ->setParameter($listed, true);
    }
}
