<?php

namespace App\Domain\Vote\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskType;
use App\Domain\Vote\Data\VoteData;
use App\Domain\Vote\Data\VoteResultData;
use App\Factory\QueryFactory;

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
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"])
            ->whereInList("task_type", [$taskTypeVotes, $taskTypeInformation])
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
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "participant_id" => $authorisation->id
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions)
            ->andWhere($authorisation_conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
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
            "detail_rating" => $data->detailRating ?? null
        ];
    }
}
