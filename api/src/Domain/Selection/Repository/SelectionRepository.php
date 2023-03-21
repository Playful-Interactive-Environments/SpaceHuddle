<?php

namespace App\Domain\Selection\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Idea\Data\IdeaData;
use App\Domain\Selection\Data\SelectionData;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskType;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\QueryFactory;
use Selective\ArrayReader\ArrayReader;

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
     * Remove task dependencies in json parameters.
     * @param string $taskId Task that contains the parameters to be cleaned.
     * @param string $dependencyId Id of the dependency.
     * @return void
     */
    public function removeSelectionDependency(string $taskId, string $dependencyId): void
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["parameter", "task_type"]);
        $query->andWhere(["id" => $taskId]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $taskType = strtolower($resultItem["task_type"]);
                if ($taskType == TaskType::SELECTION) {
                    $parameter = json_decode($resultItem["parameter"]);
                    $parameter->selectionId = "";
                    $this->queryFactory->newUpdate("task", ["parameter" => json_encode($parameter)])
                        ->andWhere(["id" => $taskId])
                        ->execute();
                    $task = new TaskRepository($this->queryFactory);
                    $task->deleteById($taskId);
                } else {
                    $parameter = json_decode($resultItem["parameter"]);
                    foreach ($parameter->selectionId as $index => $selectionId) {
                        if ($selectionId == $dependencyId) {
                            unset($parameter->selectionId[$index]);
                        }
                    }
                    $this->queryFactory->newUpdate("task", ["parameter" => json_encode($parameter)])
                        ->andWhere(["id" => $taskId])
                        ->execute();
                }
            }
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("task_input");
        $query->select(["task_id", "input_type"]);
        $query->whereInList("input_type", ["SELECTION"])
            ->andWhere(["input_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $task = new TaskRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $taskId = $resultItem["task_id"];
                $inputType = $resultItem["input_type"];
                $task->removeTaskDependency($taskId, $inputType, $id);
            }
        }

        $query = $this->queryFactory->newSelect("task_selection");
        $query->select(["task_id"]);
        $query->andWhere(["selection_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $taskId = $resultItem["task_id"];
                $this->removeSelectionDependency($taskId, $id);
            }
        }

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
     * @param AuthorisationData | null $authorisation Authorisation data
     * @return array<IdeaData> The result entity list.
     */
    public function getIdeas(string $selectionId, AuthorisationData | null $authorisation = null): array
    {
        //$taskType = strtoupper(TaskType::BRAINSTORMING);
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
            "selection_idea.order"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("selection_idea", "selection_idea.idea_id = idea.id")
            ->andWhere([
                //"task.task_type" => $taskType,
                "selection_idea.selection_id" => $selectionId,
            ])
            ->order(["selection_idea.order"]);

        $result = $this->fetchAll($query, IdeaData::class);

        if (isset($authorisation)) {
            if (is_array($result)) {
                foreach ($result as $resultItem) {
                    $this->getDetails($resultItem, $authorisation);
                }
            } elseif (is_object($result)) {
                $this->getDetails($result, $authorisation);
            }
        }

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get last modification date of ideas for the selection ID.
     * @param string $selectionId The selection ID.
     * @param AuthorisationData | null $authorisation Authorisation data
     * @return ModificationData Modification Data
     */
    public function getIdeasModification(string $selectionId, AuthorisationData | null $authorisation = null): ModificationData
    {
        $query = $this->queryFactory->newSelect("idea");
        $query->select([
            "idea.modification_date"
        ])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("selection_idea", "selection_idea.idea_id = idea.id")
            ->andWhere([
                //"task.task_type" => $taskType,
                "selection_idea.selection_id" => $selectionId,
            ])
            ->order(["modification_date" => "DESC"]);
        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Set Properties
     * @param IdeaData $data Idea data
     * @param AuthorisationData $authorisation Authorisation data
     */
    private function getDetails(IdeaData $data, AuthorisationData $authorisation): void
    {
        if ($authorisation->isParticipant()) {
            $data->isOwn = ($data->participantId == $authorisation->id);
        }
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
        //$taskTypeIdeas = strtoupper(TaskType::BRAINSTORMING);
        $query = $this->queryFactory->newSelect("idea");
        $query->select(["idea.id"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("selection", "selection.topic_id = task.topic_id")
            ->whereInList("idea.id", $ideas)
            ->andWhere([
                //"task.task_type" => $taskTypeIdeas,
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
        foreach ($ideas as $index => $value) {
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
                        "idea_id" => $value,
                        "order" => $index
                    ]
                )->execute();
            } else {
                $this->queryFactory->newUpdate(
                    "selection_idea",
                    [
                        "order" => $index
                    ]
                )
                    ->andWhere([
                        "selection_id" => $selectionId,
                        "idea_id" => $value
                    ])
                    ->execute();
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

    /**
     * Include dependent data.
     * @param string $oldId Old table primary key
     * @param string $newId Old table primary key
     * @return void
     */
    protected function cloneDependencies(string $oldId, string $newId): void
    {
        $newId = $this->queryFactory->newClone(
            "selection_idea",
            ["selection_id" => $oldId],
            ["idea_id", "order"],
            "selection_id",
            $newId
        );
    }

    /**
     * List of columns to be cloned
     * @return array<string> The array
     */
    protected function cloneColumns(): array
    {
        return [
            "name",
        ];
    }
}
