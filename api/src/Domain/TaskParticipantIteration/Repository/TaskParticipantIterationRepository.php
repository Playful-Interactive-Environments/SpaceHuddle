<?php

namespace App\Domain\TaskParticipantIteration\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\TaskParticipantIteration\Data\TaskParticipantIterationData;
use App\Domain\TaskParticipantIteration\Type\TaskParticipantIterationStateType;
use App\Factory\QueryFactory;
use DomainException;

/**
 * Repository.
 */
class TaskParticipantIterationRepository implements RepositoryInterface
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
            "task_participant_iteration",
            TaskParticipantIterationData::class,
            "task_id",
            TaskRepository::class
        );
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return TaskParticipantIterationData|array<TaskParticipantIterationData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|TaskParticipantIterationData|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["task_participant_iteration.*", "participant.symbol", "participant.color"])
            ->innerJoin("participant", "task_participant_iteration.participant_id = participant.id")
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
        $result = $this->get($conditions, ["task_participant_iteration.iteration"]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get the last entity for the task for the logged-in participant.
     * @param string $parentId The entity parent ID.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function getLast(string $parentId): ?object
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $query = $this->queryFactory->newSelect($this->getEntityName());
            $query->select(["*"])
                ->andWhere([
                    "task_id" => $parentId,
                    "participant_id" => $authorisation->id
                ])
                ->order(["iteration" => "DESC"])
                ->limit(1);

            $result = $this->fetchAll($query);
            if (!is_object($result)) {
                return null;
            }
            return $result;
        }
        return null;
    }

    /**
     * Insert session row.
     * @param object $data The session role data
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new session
     * @throws GenericException
     */
    public function insert(object $data, bool $insertDependencies = true): ?object
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $data->id = uuid_create();
            $data->participantId = $authorisation->id;
            $last = $this->getLast($data->taskId);
            if (!is_null($last))
                $data->iteration = $last->iteration + 1;
            else $data->iteration = 1;

            $usedKeys = array_values($this->translateKeys((array)$data));
            $row = $this->formatDatabaseInput($data);
            $row = $this->unsetUnused($row, $usedKeys);

            $itemCount = $this->queryFactory->newInsert($this->getEntityName(), $row)
                ->execute()->rowCount();

            if ($insertDependencies && $itemCount > 0 and array_key_exists("id", $row)) {
                $this->insertDependencies($data->id, $data);
            }

            return $this->getById($data->id);
        }
        return null;
    }

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     */
    public function update(object|array $data): ?object
    {
        if (is_object($data)) {
            $this->updateDependencies($data->id, $data);
        }

        if (!is_array($data)) {
            $usedKeys = array_values($this->translateKeys((array)$data));
            $data = $this->formatDatabaseInput($data);
            $data = $this->unsetUnused($data, $usedKeys);
        }

        $id = $data["id"];
        unset($data["id"]);
        $data["modification_date"] = date('Y-m-d H:i:s');

        $this->queryFactory->newUpdate($this->getEntityName(), $data)
            ->andWhere(["id" => $id])
            ->execute();

        return $this->getById($id);
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
            "task_id" => $data->taskId ?? null,
            "participant_id" => $data->participantId ?? null,
            "iteration" => $data->iteration ?? 0,
            "state" => $data->state ?? strtoupper(TaskParticipantIterationStateType::IN_PROGRESS),
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
        ];

        return $result;
    }
}
