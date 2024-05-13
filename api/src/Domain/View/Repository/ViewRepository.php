<?php

namespace App\Domain\View\Repository;

use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Idea\Type\IdeaSortOrder;
use App\Domain\Topic\Repository\TopicRepository;
use App\Domain\View\Data\ViewData;
use App\Factory\QueryFactory;
use function DI\add;

/**
 * Repository
 */
class ViewRepository implements RepositoryInterface
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
            "selection_view",
            ViewData::class,
            "topic_id",
            TopicRepository::class
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
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "selection_view.*",
            "selection_view.topic_id",
            "topic.title as topic_name",
            "topic.session_id",
            ])
            ->innerJoin("topic", "topic.id = selection_view.topic_id")
            ->andWhere($conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get list of entities for the session ID.
     * @param string $sessionId The entity session ID.
     * @return array<object> The result entity list.
     */
    public function getSessionList(
        string $sessionId
    ): array|null {
        return $this->get(["topic.session_id" => $sessionId]);
    }

    /**
     * Read the details of the view.
     * @param string $type Type of the view.
     * @param string $typeId The ID of the view.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @param array|null $filter Filter ideas by idea state.
     * @param int|null $count Max return records count.
     * @param string|null $countOrderType Use a different sorting for the determination of the elements contained in the return set.
     * @param string|null $countRefId Use a different sorting for the determination of the elements contained in the return set.
     * @return array<object> View details.
     */
    public function getDetails(
        string $type,
        string $typeId,
        ?string $orderType = null,
        ?string $refId = null,
        ?array $filter = null,
        ?int $count = null,
        ?string $countOrderType = null,
        ?string $countRefId = null
    ): array {
        $authorisation = $this->getAuthorisation();
        $orderType = strtolower($orderType);
        $type = strtolower($type);
        if (!$orderType) {
            $orderType = $countOrderType;
        }
        if (!$refId) {
            $refId = $countRefId;
        }
        $sortConditions = $this->getSortOrder($type, $orderType, $refId);

        $query = $this->queryFactory->newSelect("selection_view_idea_$type AS view_idea");

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
            "view_idea.order",
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
                "view_idea.order",
                "MAX(participant.symbol) AS symbol",
                "MAX(participant.color) AS color",
                "MAX(participant.id) AS participant_id",
                "COUNT(*) AS count"
            ];
        }

        if ($refId && str_contains($orderType, IdeaSortOrder::HIERARCHY)) {
            $query->select(array_merge($defaultColumns, [
                "hierarchy.category_idea_id as category_id",
                "COALESCE(category.keywords, 'zzz') AS category",
                "category.parameter AS category_parameter",
                "hierarchy.order AS hierarchy_order"
            ]));
        } elseif ($refId && str_contains($orderType, IdeaSortOrder::VIEW)) {
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

        $query->innerJoin("idea", "idea.id = view_idea.idea_id")
            ->leftJoin("participant", $participantConditions)
            ->andWhere([
                "view_idea.parent_id" => $typeId
            ])
            ->distinct(["idea.task_id", "idea.keywords", "idea.description", "idea.image", "idea.link"]);

        if (isset($filter) and count($filter) > 0) {
            $query->whereInList("idea.state", $filter);
        }

        if ($refId && str_contains($orderType, IdeaSortOrder::HIERARCHY)) {
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
        } elseif ($refId && str_contains($orderType, IdeaSortOrder::VIEW)) {
            $query->leftJoin(
                "selection_view_idea",
                ["selection_view_idea.idea_id = idea.id", "selection_view_idea.parent_id" => $refId]
            );
        }

        if ($count && ($orderType != $countOrderType || $refId != $countRefId)) {
            $ideaList = $this->getDetailSet($type, $typeId, $countOrderType, $countRefId, $filter, $count);
            if (sizeof($ideaList) > 0)
                $query->whereInList("idea.id", $ideaList);
        }

        if (count($sortConditions) > 0) {
            $query->order($sortConditions);
        }

        if ($count) {
            $query->limit($count);
        }

        $result = $this->fetchAll($query, IdeaData::class);
        $resultList = [];
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }

        $orderTypeList = IdeaRepository::convertToList($orderType);
        if (sizeof($orderTypeList) > 0) {
            $resultList = IdeaRepository::addOrderColumn($orderTypeList[0], $resultList, $refId);
            if ($orderTypeList[0] === IdeaSortOrder::INPUT) {
                foreach ($resultList as $resultItem) {
                    $resultItem->orderGroup = "$type.$typeId";
                }
            }
        }
        return $resultList;
    }

    /**
     * Read all task input ideas
     * @param string $taskId Task id
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array List of ideas
     */
    public function getTaskInputDetails(
        string $taskId,
        ?string $orderType = null,
        ?string $refId = null
    ): array {
        $query = $this->queryFactory->newSelect("task");
        $query->select([
            "parameter"
        ])
            ->andWhere(["id" => $taskId]);

        $taskData = $query->execute()->fetchAll()[0];
        $parameter = json_decode($taskData[0]);
        $resultList = [];
        if (property_exists($parameter, "input")) {
            $inputs = $parameter->input;
            foreach ($inputs as $input) {
                $data = $this->getDetails(
                    $input->view->type,
                    $input->view->id,
                    $orderType ?? $input->order,
                    $orderType ? $refId : $input->refId,
                    $input->filter,
                    $input->maxCount,
                    $input->order,
                    property_exists($input, "refId") ? $input->refId : null
                );
                $resultList = array_merge($resultList, $data);
            }
        }
        return $resultList;
    }

    /**
     * Read the last modification timestamp of the view.
     * @param string $type Type of the view.
     * @param string $typeId The ID of the view.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @param array|null $filter Filter ideas by idea state.
     * @param int|null $count Max return records count.
     * @param string|null $countOrderType Use a different sorting for the determination of the elements contained in the return set.
     * @param string|null $countRefId Use a different sorting for the determination of the elements contained in the return set.
     * @return ModificationData Modification Data
     */
    public function getDetailsModification(
        string $type,
        string $typeId,
        ?string $orderType = null,
        ?string $refId = null,
        ?array $filter = null,
        ?int $count = null,
        ?string $countOrderType = null,
        ?string $countRefId = null
    ): ModificationData {
        $orderType = strtolower($orderType);
        $type = strtolower($type);
        if (!$orderType) {
            $orderType = $countOrderType;
        }
        if (!$refId) {
            $refId = $countRefId;
        }

        $query = $this->queryFactory->newSelect("selection_view_idea_$type AS view_idea");
        $query->select(["idea.modification_date"]);

        $query->innerJoin("idea", "idea.id = view_idea.idea_id")
            ->andWhere([
                "view_idea.parent_id" => $typeId
            ]);

        if (isset($filter) and count($filter) > 0) {
            $query->whereInList("idea.state", $filter);
        }

        if ($count && ($orderType != $countOrderType || $refId != $countRefId)) {
            $ideaList = $this->getDetailSet($type, $typeId, $countOrderType, $countRefId, $filter, $count);
            $query->whereInList("idea.id", $ideaList);
        }

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Get the sort order condition
     * @param string $type Type of the view.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @return array<object> Sort order condition.
     */
    private function getSortOrder(
        string $type,
        ?string $orderType = null,
        ?string $refId = null,
    ): array {
        $sortConditions = IdeaRepository::convertOrderType($orderType, $refId);
        if (strtolower($type) == 'vote' and strtolower($orderType) == 'order') {
            $sortConditions = ["view_idea.order" => "desc"];
        } else {
            foreach ($sortConditions as $key => $order) {
                if ($order == "order") {
                    $sortConditions[$key] = "view_idea.order";
                }
            }
        }
        if (count($sortConditions) == 0) {
            if (strtolower($type) == 'vote') {
                $sortConditions = ["view_idea.order" => "desc"];
            } else {
                $sortConditions = ["view_idea.order"];
            }
        }
        return $sortConditions;
    }

    /**
     * Read the detail set of the view.
     * @param string $type Type of the view.
     * @param string $typeId The ID of the view.
     * @param string|null $orderType The order by type (value of IdeaSortOrder).
     * @param string|null $refId The referenced taskId for sorting by categories.
     * @param array|null $filter Filter ideas by idea state.
     * @param int|null $count Max return records count.
     * @return array<object> View details.
     */
    private function getDetailSet(
        string $type,
        string $typeId,
        ?string $orderType = null,
        ?string $refId = null,
        ?array $filter = null,
        ?int $count = null,
    ): array {
        $sortConditions = $this->getSortOrder($type, $orderType, $refId);

        $query = $this->queryFactory->newSelect("selection_view_idea_$type AS view_idea");

        $query->select([
            "view_idea.idea_id AS id",
            "COUNT(*) AS count"
        ]);


        $query->andWhere([
                "view_idea.parent_id" => $typeId
            ])
            ->distinct(["view_idea.idea_id"]);

        if (isset($filter) and count($filter) > 0) {
            $query->innerJoin("idea", "idea.id = view_idea.idea_id")
                ->whereInList("idea.state", $filter);
        }

        if ($refId) {
            $query->leftJoin("hierarchy", "hierarchy.sub_idea_id = view_idea.idea_id")
                ->join([
                    "category" => [
                        "table" => "idea",
                        "type" => "LEFT",
                        "conditions" => ["hierarchy.category_idea_id = category.id", "category.task_id" => $refId]
                    ]
                ])
                ->andWhere(["(category.id IS NOT NULL OR (category.id IS NULL AND hierarchy.sub_idea_id IS NULL))"]);
        }

        if ($count) {
            $query->limit($count);
        }

        if (count($sortConditions) > 0) {
            $query->order($sortConditions);
        }

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            $result = [];
            foreach ($rows as $resultItem) {
                array_push($result, $resultItem["id"]);
            }
            return $result;
        }
        return [];
    }
}
