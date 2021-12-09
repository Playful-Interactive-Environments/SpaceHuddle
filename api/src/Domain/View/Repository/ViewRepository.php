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
     * @return array<object> View details.
     */
    public function getDetails(
        string $type,
        string $typeId,
        ?string $orderType,
        ?string $refId = null,
        ?array $filter = null
    ): array {
        $sortConditions = IdeaRepository::convertOrderType($orderType, $refId);
        foreach ($sortConditions as $key => $order) {
            if ($order == "order") {
                $sortConditions[$key] = "view_idea.order";
            }
        }
        if (count($sortConditions) == 0) {
            $sortConditions = ["view_idea.order"];
        }

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

        if (count($sortConditions) > 0) {
            $query->order($sortConditions);
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
}
