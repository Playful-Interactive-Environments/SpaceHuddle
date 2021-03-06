<?php

namespace App\Domain\Task\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Module\Data\ModuleData;
use App\Domain\Module\Repository\ModuleRepository;
use App\Domain\Module\Type\ModuleState;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\Task\Data\TaskData;
use App\Domain\Task\Type\TaskState;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;
use Cake\Database\Query;
use App\Domain\Task\Type\TaskType;

/**
 * Repository
 */
class TaskRepository implements RepositoryInterface
{
    use RepositoryTrait {
        RepositoryTrait::update as private genericUpdate;
    }

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
                strtoupper(TaskState::READ_ONLY),
                strtoupper(TaskState::WAIT),
                strtoupper(TaskState::DONE)
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
        if (count($sortConditions) == 0) {
            $sortConditions = ["order"];
        }
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "task.*",
            "IFNULL(task_info.participant_count, 0) AS participant_count",
            "topic.session_id",
            "participant_task.id AS active_on_participant",
            "synchro_task.id AS synchro_task",
            "topic.order as topic_order"
        ])
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->leftJoin("task_info", "task_info.task_id = task.id")
            ->leftJoin("participant_task", "participant_task.id = task.id")
            ->leftJoin("synchro_task", "synchro_task.id = task.id")
            ->andWhere($conditions)
            ->order($sortConditions);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getDetails($resultItem);
            }
        } elseif (is_object($result)) {
            $this->getDetails($result);
        }
        return $result;
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array
    {
        $conditions = [$this->getParentIdName() => $parentId];
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            array_push($conditions, "participant_task.id IS NOT NULL");
        }
        $result = $this->get($conditions);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list of dependent entities for the task ID.
     * @param string $id The entity dependent task ID.
     * @return array<object> The result entity list.
     */
    public function getDependent(string $id): array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "task.*"
        ])
            ->innerJoin("task_input", "task_input.task_id = task.id")
            ->andWhere(["task_input.input_id" => $id]);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
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
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        if (is_object($data) && property_exists($data, "remainingTime")) {
            $data->expirationTime = $this->expirationTime($data->remainingTime);
            unset($data->remainingTime);
        } elseif (is_array($data) && array_key_exists("remaining_time", $data)) {
            $data["expiration_time"] = $this->expirationTime($data["remaining_time"]);
            unset($data["remaining_time"]);
        }
        return $this->genericUpdate($data);
    }

    /**
     * Calculate expiration time
     * @param int $remindingTime How long should the timer run?
     * @return string|null Expiration date
     */
    private function expirationTime(int|null $remindingTime): string|null
    {
        if (isset($remindingTime) && $remindingTime > 0) {
            $now = strtotime("now");
            return date("Y-m-d\TH:i:s", $now + $remindingTime);
        }
        return null;
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

                $subQueryModules = $this->queryFactory->newSelect("module")
                    ->select(["id"])
                    ->where(function ($exp, $q) {
                        return $exp->equalFields("module.id", "session.public_screen_module_id");
                    })
                    ->andWhere(["module.task_id" => $id]);

                $publicModuleId = null;
                $result = $this->queryFactory->newSelect("module")
                    ->select(["module.id"])
                    ->whereInList("module_name", $parameter->modules)
                    ->andWhere(["task_id" => $id])
                    ->execute()
                    ->fetch("assoc");
                if (is_array($result)) {
                    $publicModuleId = $result["id"];
                }

                $this->queryFactory->newUpdate("session", ["public_screen_module_id" => $publicModuleId])
                    ->where(function ($exp, $q) use ($subQueryModules) {
                        return $exp->exists($subQueryModules);
                    })
                    ->execute();

                $this->queryFactory->newDelete("module")
                    ->whereNotInList("module_name", $parameter->modules)
                    ->andWhere(["task_id" => $id])
                    ->execute();
            }
        }
    }

    /**
     * Remove task dependencies in json parameters.
     * @param string $taskId Task that contains the parameters to be cleaned.
     * @param string $dependencyType Type of the dependency.
     * @param string $dependencyId Id of the dependency.
     * @return void
     */
    public function removeTaskDependency(string $taskId, string $dependencyType, string $dependencyId): void
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["parameter", "task_type"]);
        $query->andWhere(["id" => $taskId]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $parameter = json_decode($resultItem["parameter"]);
                foreach ($parameter->input as $index => $input) {
                    if ($input->view->type == $dependencyType and $input->view->id == $dependencyId) {
                        unset($parameter->input[$index]);
                    }
                }
                $this->queryFactory->newUpdate("task", ["parameter" => json_encode($parameter)])
                    ->andWhere(["id" => $taskId])
                    ->execute();
                $taskType = strtolower($resultItem["task_type"]);
                if (
                    in_array($taskType, [TaskType::SELECTION, TaskType::CATEGORISATION, TaskType::VOTING]) and
                    count($parameter->input) == 0
                ) {
                    $this->deleteById($taskId);
                }
            }
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("task_input");
        $query->select(["task_id", "input_type"]);
        $query->whereInList("input_type", ["TASK", "VOTE"])
            ->andWhere(["input_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $taskId = $resultItem["task_id"];
                $inputType = $resultItem["input_type"];
                $this->removeTaskDependency($taskId, $inputType, $id);
            }
        }

        $query = $this->queryFactory->newSelect("task_selection");
        $query->select(["selection_id"]);
        $query->andWhere(["task_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $selection = new SelectionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $selectionId = $resultItem["selection_id"];
                $selection->deleteById($selectionId);
            }
        }

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
        $result = [
            "id" => $data->id ?? null,
            "topic_id" => $data->topicId ?? null,
            "task_type" => $data->taskType ?? null,
            "name" => $data->name ?? null,
            "description" => $data->description ?? null,
            "keywords" => $data->keywords ?? null,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
            "order" => $data->order ?? 0,
            "state" => $data->state ?? strtoupper(TaskState::WAIT),
            "expiration_time" => $data->expirationTime ?? null
        ];

        return $result;
    }
}
