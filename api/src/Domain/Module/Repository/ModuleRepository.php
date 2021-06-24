<?php

namespace App\Domain\Module\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Module\Data\ModuleData;
use App\Domain\Task\Repository\TaskRepository;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class ModuleRepository implements RepositoryInterface
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
            "module",
            ModuleData::class,
            "task_id",
            TaskRepository::class
        );
    }
}
