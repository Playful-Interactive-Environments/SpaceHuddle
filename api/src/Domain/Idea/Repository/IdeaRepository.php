<?php

namespace App\Domain\Idea\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class IdeaRepository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * The type of task involved.
     * @var string
     */
    protected string $taskType = TaskType::BRAINSTORMING;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "idea",
            IdeaData::class,
            "task_id",
            TaskRepository::class
        );

        $this->taskType = strtoupper($this->taskType);
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
        $conditions = ["id" => $id];
        if ($authorisation->isParticipant()) {
            $conditions["participant_id"] = $authorisation->id;
        }
        return $this->getAuthorisationRoleFromCondition($id, $conditions);
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(?string $id): ?string
    {
        return $this->getAuthorisationRoleFromCondition($id, ["id" => $id]);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return IdeaData|array<IdeaData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|IdeaData|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "idea.participant_id" => $authorisation->id
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["idea.*"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions);

        return $this->fetchAll($query);
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return IdeaData|null The result entity.
     * @throws GenericException
     */
    public function getById(string $id): ?IdeaData
    {
        $result = $this->get([
            "idea.id" => $id
        ]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Get list of entities for the topic ID.
     * @param string $topicId The topic ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllFromTopic(string $topicId): array
    {
        $result = $this->get([
            "task.topic_id" => $topicId,
            "task_type" => $this->taskType,
            "task.state IN" => [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ]
        ]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @return object|null The new created entity
     */
    public function insertToTopic(object $data): ?object
    {
        $taskId = $this->getTopicBrainstormingTask(
            $data->topicId,
            [strtoupper(TaskState::ACTIVE)]
        );
        if (isset($taskId)) {
            $data->taskId = $taskId;
            return $this->insert($data);
        }
        return null;
    }

    /**
     * Determine the first active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     * @param string $topicId Topic ID
     * @param array $validStates Valid states
     * @return string|null First active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     */
    public function getTopicBrainstormingTask(string $topicId, array $validStates): ?string
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"])
            ->andWhere([
                "topic_id" => $topicId,
                "task_type" => $this->taskType
            ])->whereInList("state", $validStates);

        $result = $query->execute()->fetch("assoc");
        if ($result) {
            return $result["id"];
        }
        return null;
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newDelete("vote")
            ->andWhere(["idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("hierarchy")
            ->andWhere(["category_idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("hierarchy")
            ->andWhere(["sub_idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("selection_idea")
            ->andWhere(["idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("random_idea")
            ->andWhere(["idea_id" => $id])
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
            "description" => $data->description ?? null,
            "keywords" => $data->keywords ?? null,
            "image" => $data->image ?? null,
            "link" => $data->link ?? null,
            "state" => $data->state ?? null,
            "timestamp" => $data->timestamp ?? null,
            "task_id" => $data->taskId ?? null,
            "participant_id" => $data->participantId ?? null
        ];
    }
}
