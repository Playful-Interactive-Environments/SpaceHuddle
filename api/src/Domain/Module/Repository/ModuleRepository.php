<?php

namespace App\Domain\Module\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Repository\IdeaRepository;
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

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     */
    public function getAll(string $parentId): array
    {
        $result = $this->get([$this->getParentIdName() => $parentId], ["order"]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newUpdate("session", ["public_screen_module_id" => null])
            ->andWhere(["public_screen_module_id" => $id])
            ->execute();
    }
}
