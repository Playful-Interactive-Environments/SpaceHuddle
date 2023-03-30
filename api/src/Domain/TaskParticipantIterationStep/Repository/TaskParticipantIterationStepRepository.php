<?php

namespace App\Domain\TaskParticipantIterationStep\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\TaskParticipantIterationStep\Data\TaskParticipantIterationStepData;
use App\Domain\TaskParticipantIterationStep\Type\TaskParticipantIterationStepStateType;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class TaskParticipantIterationStepRepository implements RepositoryInterface
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
            "task_participant_iteration_step",
            TaskParticipantIterationStepData::class,
            "task_id",
            TaskRepository::class
        );
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return TaskParticipantIterationStepData|array<TaskParticipantIterationStepData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|TaskParticipantIterationStepData|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["task_participant_iteration_step.*", "participant.symbol", "participant.color"])
            ->innerJoin("participant", "task_participant_iteration_step.participant_id = participant.id")
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
        $result = $this->get($conditions, ["task_participant_iteration_step.iteration", "task_participant_iteration_step.step"]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list of final entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllFinal(string $parentId): array
    {
        $conditions = ["task_participant_iteration_step.task_id" => $parentId];
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $conditions["task_participant_iteration_step.participant_id"] = $authorisation->id;
        }

        $subQueryIteration = $this->queryFactory->newSelect($this->getEntityName())
            ->select(["task_id", "participant_id", "idea_id", "max(`iteration`) AS iteration"])
            ->andWhere($conditions)
            ->group(["task_id", "participant_id", "idea_id"]);

        $subQuery = $this->queryFactory->newSelect($this->getEntityName())
            ->select([
                "task_participant_iteration_step.task_id",
                "task_participant_iteration_step.participant_id",
                "task_participant_iteration_step.idea_id",
                "task_participant_iteration_step.iteration",
                "max(task_participant_iteration_step.step) AS step"])
            ->join([
                "last_iteration_step" => [
                    "table" => $subQueryIteration,
                    "type" => "INNER",
                    "conditions" => [
                        "last_iteration_step.task_id = task_participant_iteration_step.task_id",
                        "last_iteration_step.participant_id = task_participant_iteration_step.participant_id",
                        "last_iteration_step.idea_id = task_participant_iteration_step.idea_id",
                        "last_iteration_step.iteration = task_participant_iteration_step.iteration",
                    ]
                ]
            ])
            ->andWhere($conditions)
            ->group([
                "task_participant_iteration_step.task_id",
                "task_participant_iteration_step.participant_id",
                "task_participant_iteration_step.idea_id"
            ]);


        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "task_participant_iteration_step.*",
            "participant.symbol",
            "participant.color"])
            ->innerJoin("participant", "task_participant_iteration_step.participant_id = participant.id")
            ->join([
                "last_step" => [
                    "table" => $subQuery,
                    "type" => "INNER",
                    "conditions" => [
                        "last_step.task_id = task_participant_iteration_step.task_id",
                        "last_step.participant_id = task_participant_iteration_step.participant_id",
                        "last_step.idea_id = task_participant_iteration_step.idea_id",
                        "last_step.iteration = task_participant_iteration_step.iteration",
                        "last_step.step = task_participant_iteration_step.step",
                    ]
                ]
            ])
            ->andWhere($conditions)
            ->order(["task_participant_iteration_step.iteration", "task_participant_iteration_step.step"]);

        $result = $this->fetchAll($query);
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
                ->order(["iteration" => "DESC", "step" => "DESC"])
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
            if (!is_null($last) && $data->iteration === $last->iteration)
                $data->step = $last->step + 1;
            else $data->step = 1;

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
            "iteration" => $data->iteration ?? null,
            "step" => $data->step ?? null,
            "idea_id" => $data->ideaId ?? null,
            "state" => $data->state ?? strtoupper(TaskParticipantIterationStepStateType::NEUTRAL),
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
        ];

        return $result;
    }
}
