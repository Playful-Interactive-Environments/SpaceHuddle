<?php

namespace App\Domain\Idea\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Idea\Type\IdeaSortOrder;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Domain\Task\Data\TaskData;
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
        $this->taskTypeSelection = strtoupper($this->taskTypeSelection);
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
        return $this->getAuthorisationRoleFromCondition($id, ["id" => $id], null, true, true);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @param string|null $orderType Order by type.
     * @return IdeaData|array<IdeaData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = [], ?string $refId = null, ?string $orderType = null): null|IdeaData|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        $orderType = strtolower($orderType ?? "");
        /*if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "idea.participant_id" => $authorisation->id,
                "task.state IN" => [
                    strtoupper(TaskState::ACTIVE),
                    strtoupper(TaskState::READ_ONLY)
                ]
            ];
        }*/

        $defaultColumns = [
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image_timestamp",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "idea.participant_id",
            "idea.state",
            "idea.task_id",
            "idea.timestamp",
            "GROUP_CONCAT(participant.symbol) AS symbol",
            "GROUP_CONCAT(participant.color) AS color",
            "participant.id AS participant_id",
            "COUNT(*) AS count"
        ];

        if ($authorisation->isParticipant()) {
            $defaultColumns = [
                "idea.id",
                "idea.keywords",
                "idea.description",
                "idea.image_timestamp",
                "idea.link",
                "idea.order",
                "idea.parameter",
                "idea.participant_id",
                "idea.state",
                "idea.task_id",
                "idea.timestamp",
                "MAX(participant.symbol) AS symbol",
                "MAX(participant.color) AS color",
                "MAX(participant.id) AS participant_id",
                "COUNT(*) AS count"
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        if ($refId && $orderType == IdeaSortOrder::HIERARCHY) {
            $query->select(array_merge($defaultColumns, [
                "hierarchy.category_idea_id as category_id",
                "COALESCE(category.keywords, 'zzz') AS category",
                "category.parameter AS category_parameter",
                "hierarchy.order AS hierarchy_order"
            ]));
        } elseif ($refId && $orderType == IdeaSortOrder::VIEW) {
            $query->select(array_merge($defaultColumns, [
                "selection_view_idea.order AS order"
            ]));
        } else {
            $query->select($defaultColumns);
        }

        $participantConditions = ["participant.id = idea.participant_id"];
        if ($authorisation->isParticipant()) {
            $participantConditions["participant.id"] = $authorisation->id;
        }

        $query->innerJoin("task", "task.id = idea.task_id")
            ->leftJoin("participant", $participantConditions)
            ->whereInList("task.task_type", [$this->taskType, $this->taskTypeInformation])
            ->andWhere($authorisation_conditions)
            ->andWhere($conditions)
            ->distinct(["idea.task_id", "idea.keywords", "idea.description", "idea.image", "idea.link"]);

        if ($refId && $orderType == IdeaSortOrder::HIERARCHY) {
            $query
                ->join([
                    "hierarchy" => [
                        "table" => "hierarchy_task",
                        "type" => "LEFT",
                        "conditions" => ["hierarchy.sub_idea_id = idea.id", "hierarchy.task_id" => $refId]
                    ]
                ])
                ->join([
                    "category" => [
                        "table" => "idea",
                        "type" => "LEFT",
                        "conditions" => ["hierarchy.category_idea_id = category.id", "category.task_id" => $refId]
                    ]
                ])
                ->andWhere(["(category.id IS NOT NULL OR (category.id IS NULL AND hierarchy.sub_idea_id IS NULL))"]);
        } elseif ($refId && $orderType == IdeaSortOrder::VIEW) {
            $query->leftJoin(
                "selection_view_idea",
                ["selection_view_idea.idea_id = idea.id", "selection_view_idea.parent_id" => $refId]
            );
        }

        if (count($sortConditions) > 0) {
            $query->order($sortConditions);
        }

        //echo $query->sql();

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getDetails($resultItem, $authorisation);
            }
        } elseif (is_object($result)) {
            $this->getDetails($result, $authorisation);
        }
        return $result;
    }

    /**
     * Set Properties
     * @param AuthorisationData $authorisation Authorisation data
     * @param IdeaData $data Idea data
     */
    private function getDetails(IdeaData $data, AuthorisationData $authorisation): void
    {
        if ($authorisation->isParticipant()) {
            $data->isOwn = ($data->participantId == $authorisation->id);
        }
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array<IdeaData> The result entity list.
     * @throws GenericException
     */
    public function getAllOrdered(string $parentId, ?string $orderType, ?string $refId = null): array
    {
        $authorisation = $this->getAuthorisation();
        $sortOrder = self::convertOrderType($orderType, $refId);

        //$task = $this->getParentRepository()->getById($parentId);
        $query = $this->queryFactory->newSelect("task");
        $query->select(["*"])
            ->andWhere(["id" => $parentId]);
        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            if (sizeof($rows) === 1) {
                $task = $rows[0];
            }
        }

        if ($task["task_type"] == $this->taskTypeSelection) {
            $selectionRepository = new SelectionRepository($this->queryFactory);
            $taskParameter = (object)json_decode($task["parameter"]);
            return $selectionRepository->getIdeas($taskParameter->selectionId, $authorisation);
        }

        $resultList = [];
        $result = $this->get(["idea.task_id" => $parentId], $sortOrder, $refId, $orderType);
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }

        $orderTypeList = self::convertToList($orderType);
        if (sizeof($orderTypeList) > 0) {
            return self::addOrderColumn($orderTypeList[0], $resultList, $refId);
        }
        return $resultList;
    }

    /**
     * Get list of entities for the topic ID.
     * @param string $topicId The topic ID.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAllOrderedFromTopic(string $topicId, ?string $orderType, ?string $refId = null): array
    {
        $sortOrder = self::convertOrderType($orderType, $refId);

        $resultList = [];
        $result = $this->get([
            "task.topic_id" => $topicId
        ], $sortOrder, $refId, $orderType);
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }

        $orderTypeList = self::convertToList($orderType);
        if (sizeof($orderTypeList) > 0) {
            return self::addOrderColumn($orderTypeList[0], $resultList, $refId);
        }
        return $resultList;
    }

    /**
     * Converts a comma-separated string parameter to an array.
     * @param string|null $queryParameter Comma-separated string parameter
     * @return string[] Converted array
     */
    public static function convertToList(?string $queryParameter): array
    {
        if (isset($queryParameter)) {
            if (str_starts_with($queryParameter, '[') && str_ends_with($queryParameter, ']')) {
                return explode(',', substr($queryParameter, 1, -1));
            }
            return [$queryParameter];
        }
        return [];
    }

    /**
     * Convert IdeaSortOrder to db sort column name
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array db sort column name
     */
    public static function convertOrderType(?string $orderType, ?string $refId = null): array
    {
        $orderList = [];
        $orderTypeList = self::convertToList($orderType);

        foreach ($orderTypeList as $orderType) {
            switch (strtolower($orderType)) {
                case IdeaSortOrder::INPUT:
                case IdeaSortOrder::TIMESTAMP:
                    array_push($orderList, 'timestamp');
                    break;
                case IdeaSortOrder::ALPHABETICAL:
                    array_push($orderList, 'keywords');
                    break;
                case IdeaSortOrder::STATE:
                    array_push($orderList, 'state');
                    break;
                case IdeaSortOrder::PARTICIPANT:
                    array_push($orderList, 'symbol', 'color');
                    break;
                case IdeaSortOrder::COUNT:
                    array_push($orderList, 'count');
                    break;
                case IdeaSortOrder::ORDER:
                    array_push($orderList, 'order');
                    break;
                case IdeaSortOrder::HIERARCHY:
                    if ($refId) {
                        array_push($orderList, 'category');
                    }
                    array_push($orderList, 'hierarchy_order');
                    break;
                case IdeaSortOrder::VIEW:
                    $orderList['selection_view_idea.order'] = 'desc';
                    break;
            }
        }
        return $orderList;
    }

    /**
     * Add grouping Column for IdeaSortOrder
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param array $resultList The database result table.
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array modified table
     */
    public static function addOrderColumn(?string $orderType, array $resultList, ?string $refId = null): array
    {
        $orderColumn = self::convertOrderType($orderType, $refId);

        if ($orderColumn) {
            foreach ($resultList as $resultItem) {
                $orderText = '';
                if (strtolower($orderType) == IdeaSortOrder::HIERARCHY) {
                    if (property_exists($resultItem, "category") && isset($resultItem->category)) {
                        $orderContent = $resultItem->category->name;
                        $orderText = $resultItem->category->id;
                    } else {
                        $orderContent = "undefined";
                        $orderText = $orderContent;
                    }
                } elseif (strtolower($orderType) == IdeaSortOrder::VIEW) {
                    if (property_exists($resultItem, "order") && isset($resultItem->order)) {
                        $orderContent = $resultItem->order;
                    } else {
                        $orderContent = "undefined";
                    }
                } elseif (strtolower($orderType) == IdeaSortOrder::INPUT) {
                    $orderContent = "-";
                } elseif (sizeof($orderColumn) == 1) {
                    $column = $orderColumn[0];
                    $orderContent = $resultItem->$column;
                } else {
                    switch (strtolower($orderType)) {
                        case IdeaSortOrder::PARTICIPANT:
                            if (sizeof($resultItem->avatar) > 0)
                                $orderContent = "";
                                for ($i = 0; $i < sizeof($resultItem->avatar); $i++) {
                                    $itemAvatar = $resultItem->avatar[$i]->toString();
                                    if (strlen($orderContent) > 0)
                                        $orderContent = "$orderContent $itemAvatar";
                                    else
                                        $orderContent = $itemAvatar;
                                }
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

                $resultItem->orderGroup = $orderContent;
                $resultItem->orderText = '';
                if (strtolower($orderType) == IdeaSortOrder::HIERARCHY) {
                    $resultItem->orderText = $orderText;
                } else {
                    foreach ($orderColumn as $column) {
                        if (property_exists($resultItem, $column) && isset($resultItem->$column)) {
                            $resultItem->orderText .= json_encode($resultItem->$column);
                        }
                    }
                }
            }
        }

        return $resultList;
    }
}
