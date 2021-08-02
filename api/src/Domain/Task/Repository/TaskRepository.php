<?php

namespace App\Domain\Task\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Module\Data\ModuleData;
use App\Domain\Module\Repository\ModuleRepository;
use App\Domain\Module\Type\ModuleState;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\Task\Data\TaskData;
use App\Domain\Task\Type\TaskState;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;
use Cake\Database\Query;

/**
 * Repository
 */
class TaskRepository implements RepositoryInterface
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
            "task",
            TaskData::class,
            "topic_id",
            TopicRepository::class
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param array $validStates Valid states
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    private function getAuthorisationRoleForState(
        ?string $id,
        array $validStates
    ): ?string {
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere(["id" => $id]);

        if ($authorisation->isParticipant()) {
            $query->whereInList("state", $validStates);
        }

        return $this->getAuthorisationRoleFromQuery($id, $query);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(
        ?string $id
    ): ?string {
        return $this->getAuthorisationRoleForState(
            $id,
            [
                strtoupper(TaskState::ACTIVE)
            ]
        );
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(?string $id): ?string
    {
        return $this->getAuthorisationRoleForState(
            $id,
            [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ]
        );
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|object|array
    {
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions)
            ->order($sortConditions);

        if ($authorisation->isParticipant()) {
            $query->whereInList("task.state", [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ]);
        }

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getDetails($resultItem);
            }
        } else if (is_object($result)) {
            $this->getDetails($result);
        }
        return $result;
    }

    /**
     * Get list of connected modules
     * @param TaskData $data Task data
     */
    private function getDetails(TaskData $data): void
    {
        $moduleRepository = new ModuleRepository($this->queryFactory);
        $data->modules = $moduleRepository->getAll($data->id);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter)) {
            if (!isset($parameter->modules)) {
                $parameter->modules = ["default"];
            }
            foreach ($parameter->modules as $key => $value) {
                $moduleId = uuid_create();
                $this->queryFactory->newInsert("module", [
                    "id" => $moduleId,
                    "task_id" => $id,
                    "module_name" => $value,
                    "order" => $key,
                    "state" => strtoupper(ModuleState::ACTIVE)
                ])->execute();
            }
        }
    }

    /**
     * Update dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function updateDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter)) {
            if (isset($parameter->modules)) {
                foreach ($parameter->modules as $key => $value) {
                    $existQuery = $this->queryFactory->newSelect("module");
                    $existQuery->select(["id"])
                        ->andWhere([
                            "task_id" => $id,
                            "module_name" => $value
                        ])
                        ->limit(1);

                    if ($existQuery->execute()->rowCount() == 0) {
                        $moduleId = uuid_create();
                        $this->queryFactory->newInsert("module", [
                            "id" => $moduleId,
                            "task_id" => $id,
                            "module_name" => $value,
                            "order" => $key,
                            "state" => strtoupper(ModuleState::ACTIVE)
                        ])->execute();
                    } else {
                        $this->queryFactory->newUpdate("module", ["order" => $key])
                            ->andWhere([
                                "task_id" => $id,
                                "module_name" => $value
                            ])->execute();
                    }
                }

                $this->queryFactory->newDelete("module")
                    ->whereNotInList("module_name", $parameter->modules)
                    ->andWhere(["task_id" => $id])
                    ->execute();
            }
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     * @throws GenericException
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["id"]);
        $query->andWhere(["task_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $idea = new IdeaRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $ideaId = $resultItem["id"];
                $idea->deleteById($ideaId);
            }
        }

        $query = $this->queryFactory->newSelect("module");
        $query->select(["id"]);
        $query->andWhere(["task_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $module = new ModuleRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $moduleId = $resultItem["id"];
                $module->deleteById($moduleId);
            }
        }

        $this->queryFactory->newDelete("vote")
            ->andWhere(["task_id" => $id])
            ->execute();

        $this->queryFactory->newUpdate("topic", ["active_task_id" => null])
            ->andWhere(["active_task_id" => $id])
            ->execute();
    }

    /**
     * Sets the task state.
     * @param string $taskId The task id to be updated.
     * @param string $state The new state.
     * @return object|null The updated task.
     * @throws GenericException
     */
    public function setClientState(string $taskId, string $state): object|null
    {
        $row = [
            "id" => $taskId,
            "state" => $state
        ];
        return $this->update($row);
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
            "topic_id" => $data->topicId ?? null,
            "task_type" => $data->taskType ?? null,
            "name" => $data->name ?? null,
            "description" => $data->description ?? null,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
            "order" => $data->order ?? 0,
            "state" => $data->state ?? strtoupper(TaskState::WAIT)
        ];
    }
}
