<?php

namespace App\Domain\View\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
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
     * @return array<object> View details.
     */
    public function getDetails(string $type, string $typeId): array
    {
        $query = $this->queryFactory->newSelect("selection_view_idea_$type AS view_idea");
        $query->select([
            "idea.*",
            "view_idea.order"
        ])
            ->innerJoin("idea", "idea.id = view_idea.idea_id")
            ->andWhere([
                "view_idea.parent_id" => $typeId
            ])
            ->order(["view_idea.order"]);

        $result = $this->fetchAll($query, IdeaData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }
}
