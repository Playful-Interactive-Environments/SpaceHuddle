<?php

namespace App\Domain\TaskParticipantState\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\TaskParticipantState\Data\TaskParticipantStateData;
use App\Domain\TaskParticipantState\Type\TaskParticipantStateType;
use App\Factory\QueryFactory;
use Selective\ArrayReader\ArrayReader;
use function App\Domain\SessionRole\Repository\sizeof;

/**
 * Repository.
 */
class TaskParticipantStateRepository implements RepositoryInterface
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
            "task_participant_state",
            TaskParticipantStateData::class,
            "task_id",
            TaskRepository::class
        );
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return TaskParticipantStateData|array<TaskParticipantStateData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|TaskParticipantStateData|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["task_participant_state.*", "participant.symbol", "participant.color"])
            ->innerJoin("participant", "task_participant_state.participant_id = participant.id")
            ->andWhere($conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array
    {
        $conditions = ["task_id" => $parentId];
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $conditions["participant_id"] = $authorisation->id;
        }
        $result = $this->get($conditions);
        if ($authorisation->isParticipant() && !is_object($result)) {
            $id = uuid_create();
            $query = $this->queryFactory->newInsert($this->getEntityName(), [
                "id" => $id,
                "task_id" => $parentId,
                "participant_id" => $authorisation->id,
                "count" => 0,
                "state" => TaskParticipantStateType::IN_PROGRESS,
                "parameter" => "{}"
            ]);
            $query->execute();
            $result = $this->get($conditions);
        }
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list of entities for the topic ID.
     * @param string $topicId The topic ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllFromTopic(string $topicId): array
    {
        $conditions = ["task.topic_id" => $topicId];
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "task_participant_state.task_id",
            "task.name",
            "task.keywords",
            "task.task_type",
            "SUM(task_participant_state.count) as count"
        ])
            ->innerJoin("task", "task_participant_state.task_id = task.id")
            ->andWhere($conditions)
            ->group(["task_participant_state.task_id"])
            ->order(["task.order"]);

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows)) {
            $result = [];
            foreach ($rows as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $keywords = $reader->findString("keywords");
                if (!$keywords) $keywords = $reader->findString("name");
                array_push($result, [
                    "taskId" => $reader->findString("task_id"),
                    "name" => $keywords,
                    "taskType" => $reader->findString("task_type"),
                    "count" => $reader->findInt("count") ]);
            }
            return $result;
        }
        return [];
    }

    /**
     * Get list of entities for the session ID.
     * @param string $sessionId The session ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllFromSession(string $sessionId): array
    {
        $conditions = ["topic.session_id" => $sessionId];
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $conditions["participant_id"] = $authorisation->id;
        }
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["task_participant_state.*", "participant.symbol", "participant.color"])
            ->innerJoin("participant", "task_participant_state.participant_id = participant.id")
            ->innerJoin("task", "task_participant_state.task_id = task.id")
            ->innerJoin("topic", "task.topic_id = topic.id")
            ->andWhere($conditions);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
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
            "count" => $data->count ?? 0,
            "state" => $data->state ?? strtoupper(TaskParticipantStateType::IN_PROGRESS),
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
        ];

        return $result;
    }
}
