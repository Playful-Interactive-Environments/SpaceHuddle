<?php

namespace App\Domain\Idea\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
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
     * @return IdeaData|array<IdeaData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|IdeaData|array
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
            "COUNT(*) AS count"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions)
            ->distinct(["idea.task_id", "idea.keywords", "idea.description", "idea.image", "idea.link"])
            ->order(["idea.timestamp"]);

        return $this->fetchAll($query);
    }
}
