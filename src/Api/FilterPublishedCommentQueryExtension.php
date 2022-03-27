<?php

declare(strict_types=1);

namespace App\Api;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface as QueryGenerator;
use App\Entity\Comment;
use App\Enum\WorkflowFinalStateEnum;
use Doctrine\ORM\QueryBuilder;

final class FilterPublishedCommentQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder   $queryBuilder,
        QueryGenerator $queryNameGenerator,
        string         $resourceClass,
        string         $operationName = null
    ): void
    {
        if ($resourceClass === Comment::class) {
            $queryBuilder->andWhere(sprintf(
                '%s.state = \'%s\'',
                $this->getRootAlias($queryBuilder),
                WorkflowFinalStateEnum::PUBLISHED
            ));
        }
    }

    public function applyToItem(
        QueryBuilder   $queryBuilder,
        QueryGenerator $queryNameGenerator,
        string         $resourceClass,
        array          $identifiers,
        string         $operationName = null,
        array          $context = []
    ): void
    {
        if ($resourceClass === Comment::class) {
            $queryBuilder->andWhere(sprintf(
                '%s.state = \'%s\'',
                $this->getRootAlias($queryBuilder),
                WorkflowFinalStateEnum::PUBLISHED
            ));
        }
    }

    private function getRootAlias(QueryBuilder $queryBuilder): string
    {
        $aliases = $queryBuilder->getRootAliases();
        $key     = array_key_first($aliases);

        return $aliases[$key];
    }
}
