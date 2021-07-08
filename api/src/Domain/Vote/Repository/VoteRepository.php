<?php

namespace App\Domain\Vote\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Vote\Data\VoteData;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class VoteRepository implements RepositoryInterface
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
            "vote",
            VoteData::class,
            "task_id",
            TaskRepository::class
        );
    }
}
