<?php

namespace App\Domain\Idea\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Idea\Type\IdeaSortOrder;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class IdeaRepository implements RepositoryInterface
{
    use RepositoryTrait, IdeaTableTrait {
        IdeaTableTrait::getById insteadof RepositoryTrait;
        IdeaTableTrait::deleteDependencies insteadof RepositoryTrait;
        IdeaTableTrait::formatDatabaseInput insteadof RepositoryTrait;
    }

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
     * @param array $sortConditions The ORDER BY conditions.
     * @return IdeaData|array<IdeaData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|IdeaData|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "idea.participant_id" => $authorisation->id,
                "task.state IN" => [
                    strtoupper(TaskState::ACTIVE),
                    strtoupper(TaskState::READ_ONLY)
                ]
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "idea.*",
            "participant.symbol",
            "participant.color",
            "COUNT(*) AS count"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("participant", "participant.id = idea.participant_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions)
            ->distinct(["idea.task_id", "idea.keywords", "idea.description", "idea.image", "idea.link"])
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @return array<IdeaData> The result entity list.
     * @throws GenericException
     */
    public function getAllOrdered(string $parentId, ?string $orderType): array
    {
        $sortOrder = self::convertOrderType($orderType);

        $resultList = [];
        $result = $this->get([$this->getParentIdName() => $parentId], $sortOrder);
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }

        return self::addOrderColumn($orderType, $resultList);
    }

    /**
     * Get list of entities for the topic ID.
     * @param string $topicId The topic ID.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllOrderedFromTopic(string $topicId, ?string $orderType): array
    {
        $sortOrder = self::convertOrderType($orderType);

        $resultList = [];
        $result = $this->get([
            "task.topic_id" => $topicId
        ], $sortOrder);
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }
        return self::addOrderColumn($orderType, $resultList);
    }

    /**
     * Convert IdeaSortOrder to db sort column name
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @return string|null db sort column name
     */
    private static function convertOrderType(?string $orderType): array
    {
        switch (strtolower($orderType)) {
            case IdeaSortOrder::TIMESTAMP:
                return ['timestamp'];
            case IdeaSortOrder::ALPHABETICAL:
                return ['keywords'];
            case IdeaSortOrder::STATE:
                return ['state'];
            case IdeaSortOrder::PARTICIPANT:
                return ['symbol', 'color'];
            case IdeaSortOrder::COUNT:
                return ['count'];
        }
        return [];
    }

    /**
     * Add grouping Column for IdeaSortOrder
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param array $resultList The database result table.
     * @return array modified table
     */
    private static function addOrderColumn(?string $orderType, array $resultList): array
    {
        $orderColumn = self::convertOrderType($orderType);

        if ($orderColumn) {
            foreach ($resultList as $resultItem) {
                if (sizeof($orderColumn) == 1) {
                    $column = $orderColumn[0];
                    $orderContent = $resultItem->$column;
                } else {
                    switch (strtolower($orderType)) {
                        case IdeaSortOrder::PARTICIPANT:
                            $orderContent = $resultItem->avatar->toString();
                            break;
                    }
                }

                switch (strtolower($orderType)) {
                    case IdeaSortOrder::TIMESTAMP:
                        $orderContent = substr($orderContent, 0, strlen($orderContent) - 3);
                        break;
                    case IdeaSortOrder::ALPHABETICAL:
                        $orderContent = substr($orderContent, 0, 1);
                        break;
                }

                $resultItem->order = $orderContent;
            }
        }

        return $resultList;
    }
}
