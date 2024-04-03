<?php

namespace App\Domain\Category\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Category\Data\CategoryData;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Idea\Repository\IdeaTableTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\QueryFactory;
use Selective\ArrayReader\ArrayReader;
use function DI\add;

/**
 * Repository
 */
class CategoryRepository implements RepositoryInterface
{
    use RepositoryTrait, IdeaTableTrait {
        IdeaTableTrait::getById insteadof RepositoryTrait;
        IdeaTableTrait::deleteDependencies insteadof RepositoryTrait;
        IdeaTableTrait::cloneDependencies insteadof RepositoryTrait;
        IdeaTableTrait::cloneColumns insteadof RepositoryTrait;
        IdeaTableTrait::formatDatabaseInput insteadof RepositoryTrait;
        IdeaTableTrait::lastModificationByConditions insteadof RepositoryTrait;
    }

    /**
     * The type of task involved.
     * @var string
     */
    protected string $taskType = TaskType::CATEGORISATION;

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
            CategoryData::class,
            "task_id",
            TaskRepository::class
        );

        $this->taskType = strtoupper($this->taskType);
        $this->taskTypeSelection = strtoupper($this->taskTypeSelection);
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
                "task.state IN" => [
                    strtoupper(TaskState::ACTIVE),
                    strtoupper(TaskState::READ_ONLY),
                    strtoupper(TaskState::WAIT),
                    strtoupper(TaskState::DONE)
                ]
            ];
        }
        return $authorisation_conditions;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return CategoryData|array<CategoryData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|CategoryData|array
    {
        if (count($sortConditions) == 0) {
            $sortConditions = ["order"];
        }

        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
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
            "idea.timestamp"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get list of ideas for the category ID.
     * @param string $categoryId The category ID.
     * @return array<IdeaData> The result entity list.
     */
    public function getIdeas(string $categoryId): array
    {
        $taskType = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select([
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
            "hierarchy.order"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("hierarchy", "hierarchy.sub_idea_id = idea.id")
            ->andWhere([
                "task.task_type" => $taskType,
                "hierarchy.category_idea_id" => $categoryId,
            ])
            ->order(["hierarchy.order"]);

        $result = $this->fetchAll($query, IdeaData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get last modification date of ideas for the category ID.
     * @param string $categoryId The category ID.
     * @return ModificationData Modification Data
     */
    public function getIdeasModification(string $categoryId): ModificationData
    {
        $taskType = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select([
            "idea.modification_date",
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("hierarchy", "hierarchy.sub_idea_id = idea.id")
            ->andWhere([
                "task.task_type" => $taskType,
                "hierarchy.category_idea_id" => $categoryId,
            ])
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Checks if the ideas fit the category.
     * @param string $categoryId The category ID.
     * @param array $ideas The list of ideas to add.
     * @param bool $lookForConnected If true, only ideas already associated with the category are valid.
     * @return bool If true, the data match.
     */
    public function ideasAgreeWithCategory(string $categoryId, array $ideas, bool $lookForConnected = false): bool
    {
        $taskTypeCategory = strtoupper(TaskType::CATEGORISATION);
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["idea.id"])
            ->join([
                "idea_task" => [
                    "table" => "task",
                    "type" => "INNER",
                    "conditions" => "idea_task.id = idea.task_id"
                ],
                "idea_topic" => [
                    "table" => "topic",
                    "type" => "INNER",
                    "conditions" => "idea_topic.id = idea_task.topic_id"
                ],
                "category" => [
                    "table" => "idea",
                    "type" => "INNER",
                    "conditions" => ["category.id" => $categoryId]
                ],
                "category_task" => [
                    "table" => "task",
                    "type" => "INNER",
                    "conditions" => "category_task.id = category.task_id"
                ],
                "category_topic" => [
                    "table" => "topic",
                    "type" => "INNER",
                    "conditions" => "category_topic.id = category_task.topic_id"
                ]
            ])
            ->whereInList("idea.id", $ideas)
            ->andWhere([
                "category_topic.session_id = idea_topic.session_id",
                "category_task.task_type" => $taskTypeCategory,
            ]);

        if ($lookForConnected) {
            $query->innerJoin(
                "hierarchy",
                "hierarchy.sub_idea_id = idea.id AND hierarchy.category_idea_id = category.id"
            );
        }

        return ($query->execute()->rowCount() == sizeof($ideas));
    }

    /**
     * Add a list of ideas to a category.
     * @param string $categoryId The category ID.
     * @param array $ideas The list of ideas to add.
     * @return void
     */
    public function addIdeas(string $categoryId, array $ideas): void
    {
        $taskId = $this->queryFactory->newSelect("idea")
            ->select(["task_id"])
            ->andWhere(["id" => $categoryId])
            ->execute()
            ->fetchColumn(0);

        foreach ($ideas as $index => $value) {
            $existQuery = $this->queryFactory->newSelect("hierarchy");
            $existQuery->select(["sub_idea_id"])
                ->andWhere([
                    "category_idea_id" => $categoryId,
                    "sub_idea_id" => $value
                ])
                ->limit(1);

            if ($existQuery->execute()->rowCount() == 0) {
                $this->queryFactory->newInsert(
                    "hierarchy",
                    [
                        "category_idea_id" => $categoryId,
                        "sub_idea_id" => $value,
                        "order" => $index
                    ]
                )->execute();
            } else {
                $this->queryFactory->newUpdate(
                    "hierarchy",
                    [
                        "order" => $index
                    ]
                )
                    ->andWhere([
                        "category_idea_id" => $categoryId,
                        "sub_idea_id" => $value
                    ])
                    ->execute();
            }

            //each idea can only be assigned to one category
            //delete an idea from the previous category when reassigning it
            $subQueryIdeas = $this->queryFactory->newSelect("idea")
                ->select(["id"])
                ->where(function ($exp, $q) {
                    return $exp->equalFields("idea.id", "hierarchy.category_idea_id");
                })
                ->andWhere(["idea.task_id" => $taskId]);

            $this->queryFactory->newDelete("hierarchy")
                ->where(function ($exp, $q) use ($subQueryIdeas) {
                    return $exp->exists($subQueryIdeas);
                })
                ->andWhere([
                    "category_idea_id !=" => $categoryId,
                    "sub_idea_id" => $value
                ])
                ->execute();
        }
    }

    /**
     * Delete a list of ideas from a category.
     * @param string $categoryId The category ID.
     * @param array $ideas The list of ideas to add.
     * @return void
     */
    public function deleteIdeas(string $categoryId, array $ideas): void
    {
        $this->queryFactory->newDelete("hierarchy")
            ->whereInList("sub_idea_id", $ideas)
            ->andWhere(["category_idea_id" => $categoryId])
            ->execute();
    }
}
