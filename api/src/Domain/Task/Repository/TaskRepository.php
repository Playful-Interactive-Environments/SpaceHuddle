<?php

namespace App\Domain\Task\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Module\Repository\ModuleRepository;
use App\Domain\Module\Type\ModuleState;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\Task\Data\TaskData;
use App\Domain\Task\Type\TaskState;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;

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
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(
        ?string $id
    ): ?string {
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere(["id" => $id]);

        if ($authorisation->isParticipant()) {
            $query->whereInList("state", [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ]);
        }

        return $this->getAuthorisationRoleFromQuery($id, $query);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|object|array
    {
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions);

        if ($authorisation->isParticipant()) {
            $query->whereInList("state", [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ]);
        }

        return $this->fetchAll($query);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        $moduleId = uuid_create();

        if (is_object($parameter)) {
            $this->queryFactory->newInsert("module", [
                "id" => $moduleId,
                "task_id" => $id,
                "module_name" => $parameter->taskType,
                "order" => 1,
                "state" => strtoupper(ModuleState::ACTIVE)
            ])->execute();
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
            //TODO: Implement IdeaRepository
            #$idea = new IdeaRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $ideaId = $resultItem["id"];
                #$idea->deleteById($ideaId);
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
