<?php

namespace App\Domain\Selection\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Selection\Data\SelectionData;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class SelectionRepository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "selection",
            SelectionData::class,
            "topic_id",
            TopicRepository::class
        );
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newDelete("selection_idea")
            ->andWhere(["selection_id" => $id])
            ->execute();
    }
}
