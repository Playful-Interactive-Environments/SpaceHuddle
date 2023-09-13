<?php

namespace App\Domain\Vote\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskType;
use App\Domain\Vote\Data\VoteData;
use App\Domain\Vote\Data\VoteResultData;
use App\Domain\Vote\Data\VoteResultDetailData;
use App\Factory\QueryFactory;
use Selective\ArrayReader\ArrayReader;

/**
 * Repository.
 */
class VoteRepository implements RepositoryInterface
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
            "vote",
            VoteData::class,
            "task_id",
            TaskRepository::class
        );
    }

    /**
     * Checks if the task fit the voting task type.
     * @param string $taskId The voting task ID.
     * @return bool If true, the data match.
     */
    public function taskHasCorrectTaskType(string $taskId): bool
    {
        $taskTypeVotes = strtoupper(TaskType::VOTING);
        $taskTypeInformation = strtoupper(TaskType::INFORMATION);
        $taskTypePlaying = strtoupper(TaskType::PLAYING);
        $taskTypeBrainstorming = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"])
            ->whereInList("task_type", [$taskTypeVotes, $taskTypeInformation, $taskTypePlaying, $taskTypeBrainstorming])
            ->andWhere([
                "id" => $taskId
            ]);

        return ($query->execute()->rowCount() == 1);
    }

    /**
     * Checks if the voting is already done.
     * @param string $taskId Task Id to be checked.
     * @param string $ideaId Idea Id to be checked.
     * @return bool If true, the vote exists.
     */
    public function votingExists(string $taskId, string $ideaId): bool
    {
        $authorisation = $this->getAuthorisation();
        $conditions = [
            "task_id" => $taskId,
            "idea_id" => $ideaId
        ];
        if ($authorisation->isParticipant()) {
            $conditions["participant_id"] = $authorisation->id;
        } else {
            array_push($conditions, "participant_id IS NULL");
        }

        $query = $this->queryFactory->newSelect("vote");
        $query->select(["id"])
            ->andWhere($conditions);

        return ($query->execute()->rowCount() >= 1);
    }

    /**
     * Checks if the ideas fit the voting task.
     * @param string $taskId The voting task ID.
     * @param string $ideaId The ideas to be voted.
     * @return bool If true, the data match.
     */
    public function ideaAgreeWithTask(string $taskId, string $ideaId): bool
    {
        $taskTypeIdeas = strtoupper(TaskType::BRAINSTORMING);
        $taskTypeVotes = strtoupper(TaskType::VOTING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["idea.id"])
            ->join([
                "idea_task" => [
                    "table" => "task",
                    "type" => "INNER",
                    "conditions" => "idea_task.id = idea.task_id"
                ],
                "vote_task" => [
                    "table" => "task",
                    "type" => "INNER",
                    "conditions" => "vote_task.topic_id = idea_task.topic_id"
                ]
            ])
            ->andWhere([
                "vote_task.id" => $taskId,
                "idea.id" => $ideaId,
            ]);

        return ($query->execute()->rowCount() == 1);
    }

    /**
     * Gets the authorisation condition for the entity.
     * @param AuthorisationData $authorisation Current authorisation data.
     * @return array authorisation condition
     */
    protected function getAuthorisationCondition(AuthorisationData $authorisation): array
    {
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "participant_id" => $authorisation->id
            ];
        }
        return $authorisation_conditions;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return IdeaData|array<IdeaData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|object|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["vote.*", "participant.symbol", "participant.color"])
            ->leftJoin("participant", "participant.id = vote.participant_id")
            ->andWhere($conditions)
            ->andWhere($authorisation_conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get result for as specific parameter.
     * @param string $taskId The task ID.
     * @return array<object> The voting result.
     * @throws GenericException
     */
    public function getParameterResult(string $taskId, string $parameterName): array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["idea_id", "parameter"])
            ->andWhere([
                "task_id" => $taskId
            ]);

        $rows = $query->execute()->fetchAll("assoc");
        $sum = [];
        if (is_array($rows) and sizeof($rows) > 0) {
            foreach ($rows as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $idea_id = $reader->findString("idea_id");
                if (!array_key_exists($idea_id, $sum)) $sum[$idea_id] = [
                    "sum" => 0,
                    "count" => 0
                ];
                $parameter = (object)json_decode($reader->findString("parameter") ?? "{}");
                if (property_exists($parameter, $parameterName) && $parameter->$parameterName > 0) {
                    $sum[$idea_id]["sum"] += $parameter->$parameterName;
                    $sum[$idea_id]["count"] += 1;
                }
            }
        }
        $result = [];
        foreach($sum as $idea_id => $item) {
            $item["ideaId"] = $idea_id;
            $item["avg"] = 0;
            if ($item["count"] > 0)
                $item["avg"] = $item["sum"]  / $item["count"];
            array_push($result, $item);
        }
        return $result;
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByConditions(array $conditions = []): ModificationData
    {
        return $this->lastModificationByConditionsAuthorised($conditions);
    }

    /**
     * Read the result of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return array<object> The voting result.
     */
    public function getResult(string $taskId): array
    {
        $query = $this->queryFactory->newSelect("vote_result");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "vote_result.sum_rating AS rating",
            "vote_result.sum_detail_rating AS detail_rating",
            "vote_result.count_participant AS count_participant"
        ])
            ->innerJoin("idea", "idea.id = vote_result.idea_id")
            ->andWhere([
                "vote_result.task_id" => $taskId
            ])
            ->order(["detail_rating" => "desc"]);

        $result = $this->fetchAll($query, VoteResultData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read the result details of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return array<object> The voting result.
     */
    public function getResultDetails(string $taskId): array
    {
        $query = $this->queryFactory->newSelect("vote_result_detail");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "vote_result_detail.rating",
            "vote_result_detail.detail_rating",
            "vote_result_detail.count_participant"
        ])
            ->innerJoin("idea", "idea.id = vote_result_detail.idea_id")
            ->andWhere([
                "vote_result_detail.task_id" => $taskId
            ])
            ->order(["idea.order" => "asc", "rating" => "desc"]);

        $result = $this->fetchAll($query, VoteResultDetailData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read the result of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return array<object> The voting result.
     */
    public function getParentResult(string $taskId): array
    {
        $subQuery = $this->queryFactory->newSelect("hierarchy_idea")
            ->select(["parent_idea_id"])
            ->where(function ($exp, $q) {
                return $exp->equalFields("hierarchy_idea.parent_idea_id", "idea.id");
            });

        $query = $this->queryFactory->newSelect("idea");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "IFNULL(vote_result_parent.sum_rating, 0) AS rating",
            "IFNULL(vote_result_parent.sum_detail_rating, 0) AS detail_rating",
            "IFNULL(vote_result_parent.count_participant, 0) AS count_participant"
        ])
            ->leftJoin("vote_result_parent", "idea.id = vote_result_parent.idea_id")
            ->andWhere([
                "idea.task_id" => $taskId
            ])
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->order(["idea.order" => "asc"]);

        $result = $this->fetchAll($query, VoteResultData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read the result details of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return array<object> The voting result.
     */
    public function getParentResultDetails(string $taskId): array
    {
        $subQuery = $this->queryFactory->newSelect("hierarchy_idea")
            ->select(["parent_idea_id"])
            ->where(function ($exp, $q) {
                return $exp->equalFields("hierarchy_idea.parent_idea_id", "idea.id");
            });

        $query = $this->queryFactory->newSelect("idea");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "IFNULL(vote_result_parent_detail.rating, 0) AS rating",
            "IFNULL(vote_result_parent_detail.detail_rating, 0) AS detail_rating",
            "IFNULL(vote_result_parent_detail.count_participant, 0) AS count_participant"
        ])
            ->leftJoin("vote_result_parent_detail", "idea.id = vote_result_parent_detail.idea_id")
            ->andWhere([
                "idea.task_id" => $taskId
            ])
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->order(["idea.order" => "asc", "rating" => "desc"]);

        $result = $this->fetchAll($query, VoteResultDetailData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read the result of the voting for the parent idea ID.
     * @param string $parent_id The parent idea ID.
     * @return array<object> The voting result.
     */
    public function getHierarchyResult(string $parent_id): array
    {
        $query = $this->queryFactory->newSelect("hierarchy_idea");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "IFNULL(vote_result_hierarchy.sum_rating, 0) AS rating",
            "IFNULL(vote_result_hierarchy.sum_detail_rating, 0) AS detail_rating",
            "IFNULL(vote_result_hierarchy.count_participant, 0) AS count_participant"
        ])
            ->innerJoin("idea", "idea.id = hierarchy_idea.child_idea_id")
            ->leftJoin("vote_result_hierarchy", [
                "vote_result_hierarchy.parent_idea_id = hierarchy_idea.parent_idea_id",
                "vote_result_hierarchy.idea_id = hierarchy_idea.child_idea_id"
                ])
            ->andWhere([
                "hierarchy_idea.parent_idea_id" => $parent_id
            ])
            ->order(["hierarchy_idea.order" => "asc"]);

        $result = $this->fetchAll($query, VoteResultData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read the result detail of the voting for the parent idea ID.
     * @param string $parent_id The parent idea ID.
     * @return array<object> The voting result.
     */
    public function getHierarchyResultDetail(string $parent_id): array
    {
        $query = $this->queryFactory->newSelect("hierarchy_idea");
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "IFNULL(vote_result_hierarchy_detail.rating, 0) AS rating",
            "IFNULL(vote_result_hierarchy_detail.detail_rating, 0) AS detail_rating",
            "IFNULL(vote_result_hierarchy_detail.count_participant, 0) AS count_participant"
        ])
            ->innerJoin("idea", "idea.id = hierarchy_idea.child_idea_id")
            ->leftJoin("vote_result_hierarchy_detail", [
                "vote_result_hierarchy_detail.parent_idea_id = hierarchy_idea.parent_idea_id",
                "vote_result_hierarchy_detail.idea_id = hierarchy_idea.child_idea_id"
            ])
            ->andWhere([
                "hierarchy_idea.parent_idea_id" => $parent_id
            ])
            ->order(["hierarchy_idea.order" => "asc"]);

        $result = $this->fetchAll($query, VoteResultDetailData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     */
    public function getAllForHierarchy(string $parentId): array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "participant_id" => $authorisation->id
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["vote.*"])
            ->innerJoin("hierarchy_idea", "hierarchy_idea.child_idea_id = vote.idea_id")
            ->andWhere(["hierarchy_idea.parent_idea_id" => $parentId])
            ->andWhere($authorisation_conditions)
            ->order("hierarchy_idea.order");

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Read last modification timestamp of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return ModificationData Modification Data
     */
    public function getResultModification(string $taskId): ModificationData
    {
        $query = $this->queryFactory->newSelect("vote_result");
        $query->select([
            "vote_result.modification_date",
        ])
            ->andWhere([
                "vote_result.task_id" => $taskId
            ])
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Read last modification timestamp of the voting for the task ID.
     * @param string $taskId The task ID.
     * @return ModificationData Modification Data
     */
    public function getParentResultModification(string $taskId): ModificationData
    {
        $subQuery = $this->queryFactory->newSelect("hierarchy_idea")
            ->select(["parent_idea_id"])
            ->where(function ($exp, $q) {
                return $exp->equalFields("hierarchy_idea.parent_idea_id", "idea.id");
            });

        $query = $this->queryFactory->newSelect("idea");
        $query->select([
            "idea.modification_date",
        ])
            ->andWhere([
                "idea.task_id" => $taskId
            ])
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Read last modification timestamp of the voting for the parent idea ID.
     * @param string $parent_id The parent idea ID.
     * @return ModificationData Modification Data
     */
    public function getHierarchyResultModification(string $parent_id): ModificationData
    {
        $query = $this->queryFactory->newSelect("hierarchy_idea");
        $query->select([
            "idea.modification_date"
        ])
            ->innerJoin("idea", "idea.id = hierarchy_idea.child_idea_id")
            ->andWhere([
                "hierarchy_idea.parent_idea_id" => $parent_id
            ])
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Get last modification timestamp of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return ModificationData Modification Data
     */
    public function getAllForHierarchyModification(string $parentId): ModificationData
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "participant_id" => $authorisation->id
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["vote.modification_date"])
            ->innerJoin("hierarchy_idea", "hierarchy_idea.child_idea_id = vote.idea_id")
            ->andWhere(["hierarchy_idea.parent_idea_id" => $parentId])
            ->andWhere($authorisation_conditions)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
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
            "participant_id" => $data->participantId ?? null,
            "idea_id" => $data->ideaId ?? null,
            "rating" => $data->rating ?? null,
            "detail_rating" => $data->detailRating ?? null,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null
        ];
    }
}
