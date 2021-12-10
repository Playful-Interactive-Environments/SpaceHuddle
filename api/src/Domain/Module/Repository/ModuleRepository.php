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
     * Get list of used modules from the user for the task type.
     * @param string $taskType The module task type.
     * @return array<object> The result entity list.
     */
    public function getUserModules(string $taskType): array
    {
        $authorisation = $this->getAuthorisation();
        $conditions = ["task_type" => $taskType];
        if ($authorisation->isUser()) {
            $conditions["user_id"] = $authorisation->id;
        }

        $query = $this->queryFactory->newSelect("user_module");
        $query->select(["module_name"])
            ->andWhere($conditions)
            ->order("module_name")
            ->distinct("module_name");

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            $result = [];
            foreach ($rows as $resultItem) {
                array_push($result, $resultItem["module_name"]);
            }
            return $result;
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

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "task_id" => $data->taskId ?? null,
            "module_name" => $data->name ?? null,
            "order" => $data->order ?? null,
            "state" => $data->state ?? null,
            "sync_public_participant" => $data->syncPublicParticipant,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null
        ];
    }
}
