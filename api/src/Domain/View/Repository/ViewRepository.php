<?php

namespace App\Domain\View\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Idea\Repository\IdeaRepository;
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
        $type = strtolower($type);
        if (!$orderType) {
            $orderType = $countOrderType;
        }
        if (!$refId) {
            $refId = $countRefId;
        }
        $sortConditions = $this->getSortOrder($type, $orderType, $refId);

        $query = $this->queryFactory->newSelect("selection_view_idea_$type AS view_idea");

        if ($refId) {
            $query->select([
                "idea.*",
                "view_idea.order",
                "participant.symbol",
                "participant.color",
                "idea.participant_id",
                "hierarchy.category_idea_id as category_id",
                "COALESCE(category.keywords, 'zzz') AS category",
                "category.parameter AS category_parameter",
                "hierarchy.order AS hierarchy_order",
                "COUNT(*) AS count"
            ]);
        } else {
            $query->select([
                "idea.*",
                "view_idea.order",
                "participant.symbol",
                "participant.color",
                "COUNT(*) AS count"
            ]);
        }


        $query->innerJoin("idea", "idea.id = view_idea.idea_id")
            ->leftJoin("participant", "participant.id = idea.participant_id")
            ->andWhere([
                "view_idea.parent_id" => $typeId
            ])
            ->distinct(["idea.task_id", "idea.keywords", "idea.description", "idea.image", "idea.link"]);

        if (isset($filter) and count($filter) > 0) {
            $query->whereInList("idea.state", $filter);
        }

        if ($refId) {
            $query->leftJoin("hierarchy", "hierarchy.sub_idea_id = idea.id")
                ->join([
                    "category" => [
                        "table" => "idea",
                        "type" => "LEFT",
                        "conditions" => ["hierarchy.category_idea_id = category.id", "category.task_id" => $refId]
                    ]
                ])
                ->andWhere(["(category.id IS NOT NULL OR (category.id IS NULL AND hierarchy.sub_idea_id IS NULL))"]);
        }


        if ($count && ($orderType != $countOrderType || $refId != $countRefId)) {
            $ideaList = $this->getDetailSet($type, $typeId, $countOrderType, $countRefId, $filter, $count);
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
            return IdeaRepository::addOrderColumn($orderTypeList[0], $resultList, $refId);
        }
        return $resultList;
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
