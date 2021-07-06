<?php

namespace App\Domain\Category\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Category\Data\CategoryData;
use App\Domain\Idea\Repository\IdeaTableTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class CategoryRepository implements RepositoryInterface
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
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return CategoryData|array<CategoryData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|CategoryData|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "task.state IN" => [
                    strtoupper(TaskState::ACTIVE),
                    strtoupper(TaskState::READ_ONLY)
                ]
            ];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["idea.*"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions);

        return $this->fetchAll($query);
    }
}
