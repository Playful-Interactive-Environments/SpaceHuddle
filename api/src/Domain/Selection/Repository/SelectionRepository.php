<?php

namespace App\Domain\Selection\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Selection\Data\SelectionData;
use App\Domain\Task\Type\TaskType;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class SelectionRepository implements RepositoryInterface
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
            "selection",
            SelectionData::class,
            "topic_id",
            TopicRepository::class
        );
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newDelete("selection_idea")
            ->andWhere(["selection_id" => $id])
            ->execute();
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
            "topic_id" => $data->topicId ?? null,
            "name" => $data->name ?? null
        ];
    }

    /**
     * Get list of ideas for the selection ID.
     * @param string $selectionId The selection ID.
     * @return array<IdeaData> The result entity list.
     */
    public function getIdeas(string $selectionId): array
    {
        $taskType = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["idea.*"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("selection_idea", "selection_idea.idea_id = idea.id")
            ->andWhere([
                "task.task_type" => $taskType,
                "selection_idea.selection_id" => $selectionId,
            ]);

        $result = $this->fetchAll($query, IdeaData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Checks if the ideas fit the selection.
     * @param string $selectionId The selection ID.
     * @param array $ideas The list of ideas to add.
     * @param bool $lookForConnected If true, only ideas already associated with the selection are valid.
     * @return bool If true, the data match.
     */
    public function ideasAgreeWithSelection(string $selectionId, array $ideas, bool $lookForConnected = false): bool
    {
        $taskTypeIdeas = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["idea.id"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("selection", "selection.topic_id = task.topic_id")
            ->whereInList("idea.id", $ideas)
            ->andWhere([
                "task.task_type" => $taskTypeIdeas,
                "selection.id" => $selectionId,
            ]);

        if ($lookForConnected) {
            $query->innerJoin(
                "selection_idea",
                "selection_idea.idea_id = idea.id"
            )
                ->andWhere([
                    "selection_idea.selection_id" => $selectionId,
                ]);
        }

        return ($query->execute()->rowCount() == sizeof($ideas));
    }

    /**
     * Add a list of ideas to a category.
     * @param string $selectionId The selection ID.
     * @param array $ideas The list of ideas to add.
     * @return void
     */
    public function addIdeas(string $selectionId, array $ideas): void
    {
        foreach ($ideas as $value) {
            $existQuery = $this->queryFactory->newSelect("selection_idea");
            $existQuery->select(["idea_id"])
                ->andWhere([
                    "selection_id" => $selectionId,
                    "idea_id" => $value
                ])
                ->limit(1);

            if ($existQuery->execute()->rowCount() == 0) {
                $this->queryFactory->newInsert(
                    "selection_idea",
                    [
                        "selection_id" => $selectionId,
                        "idea_id" => $value
                    ]
                )->execute();
            }
        }
    }

    /**
     * Delete a list of ideas from a category.
     * @param string $selectionId The selection ID.
     * @param array $ideas The list of ideas to add.
     * @return void
     */
    public function deleteIdeas(string $selectionId, array $ideas): void
    {
        $this->queryFactory->newDelete("selection_idea")
            ->whereInList("idea_id", $ideas)
            ->andWhere(["selection_id" => $selectionId])
            ->execute();
    }
}
