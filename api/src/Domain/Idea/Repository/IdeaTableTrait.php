<?php

namespace App\Domain\Idea\Repository;

use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Domain\Idea\Data\ImageData;
use Selective\ArrayReader\ArrayReader;
use function DI\string;

trait IdeaTableTrait
{
    /**
     * The type of selection task.
     * @var string
     */
    protected string $taskTypeSelection = TaskType::SELECTION;

    /**
     * The type of information task.
     * @var string
     */
    protected string $taskTypeInformation = TaskType::INFORMATION;

    /**
     * The type of playing task.
     * @var string
     */
    protected string $taskTypePlaying = TaskType::PLAYING;

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return object|null The result entity.
     */
    public function getById(string $id): ?object
    {
        $result = $this->get([
            "idea.id" => $id
        ]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByConditions(array $conditions = []): ModificationData
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["idea.modification_date"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere(["task.task_type" => $this->taskType])
            ->andWhere($conditions)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Get image for entity
     * @param string $id The entity ID.
     * @return object|null The result image.
     */
    public function getImage(string $id): ?object
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["idea.id", "idea.image", "idea.image_timestamp"])
            ->andWhere([
                "idea.id" => $id
            ]);

        $result = $this->fetchAll($query, ImageData::class);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Set the image for a entity row.
     * @param object $data The data to be inserted
     */
    public function setImage(object $data): array | object | null
    {
        $image_data = [
            "image" => $data->image ?? null,
            "image_timestamp" => date("Y-m-d H:i:s")
        ];

        $this->queryFactory->newUpdate($this->getEntityName(), $image_data)
            ->andWhere(["id" => $data->id])
            ->execute();

        $query = $this->queryFactory->newSelect("idea");
        $result = $query->select(["image_timestamp"])
            ->andWhere(["id" => $data->id])
            ->execute()
            ->fetch("assoc");
        if ($result) {
            return $result;
        }
        return null;
    }

    /**
     * Delete image from entity
     * @param string $id The entity ID.
     * @return void
     */
    public function deleteImage(string $id): void
    {
        $image_data = [
            "image" => null,
            "image_timestamp" => null
        ];

        $this->queryFactory->newUpdate($this->getEntityName(), $image_data)
            ->andWhere(["id" => $id])
            ->execute();
    }

    /**
     * Get list of entities for the topic ID.
     * @param string $topicId The topic ID.
     * @return array<object> The result entity list.
     */
    public function getAllFromTopic(string $topicId): array
    {
        $result = $this->get([
            "task.topic_id" => $topicId
        ]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Has changes for the topic ID
     * @param string $topicId The entity parent ID.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByTopicId(string $topicId): ModificationData
    {
        return $this->lastModificationByConditions([
            "task.topic_id" => $topicId
        ]);
    }

    /**
     * Determine the first active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     * @param string $topicId Topic ID
     * @param array $validStates Valid states
     * @param bool $validTimer Valid if timer is active
     * @return string|null First active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     */
    public function getTopicTask(string $topicId, array $validStates, bool $validTimer = false): ?string
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"])
            ->andWhere([
                "topic_id" => $topicId,
                "task_type" => $this->taskType
            ]);

        if (sizeof($validStates) > 0) {
            $query->whereInList("state", $validStates);
        }

        if ($validTimer) {
            $query->andWhere(["(expiration_time IS NULL OR expiration_time >= current_timestamp())"]);
        }

        $result = $query->execute()->fetch("assoc");
        if ($result) {
            return $result["id"];
        }
        return null;
    }

    /**
     * Checks if the task fit the idea task type.
     * @param string $taskId The task ID.
     * @return bool If true, the data match.
     */
    public function taskHasCorrectTaskType(string $taskId): bool
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"])
            ->whereInList("task_type", [$this->taskType, $this->taskTypeSelection, $this->taskTypeInformation, $this->taskTypePlaying])
            ->andWhere([
                "id" => $taskId
            ]);

        return ($query->execute()->rowCount() == 1);
    }

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @param array $validStates Valid states
     * @return object|null The new created entity
     */
    public function insertToTopic(object $data, array $validStates = []): ?object
    {
        $taskId = $this->getTopicTask(
            $data->topicId,
            $validStates
        );
        if (isset($taskId)) {
            $data->taskId = $taskId;
            return $this->insert($data);
        }
        return null;
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newDelete("task_participant_iteration_step")
            ->andWhere(["idea_id" => $id])
            ->execute();

        $query = $this->queryFactory->newSelect("task_input");
        $query->select(["task_id", "input_type"]);
        $query->whereInList("input_type", ["HIERARCHY"])
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

        $this->queryFactory->newDelete("vote")
            ->andWhere(["idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("hierarchy")
            ->andWhere(["category_idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("hierarchy")
            ->andWhere(["sub_idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("selection_idea")
            ->andWhere(["idea_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("random_idea")
            ->andWhere(["idea_id" => $id])
            ->execute();
    }


    /**
     * Checks whether the user has already submitted the idea.
     * @param object $data The data to be inserted
     * @return bool If true, the idea has already been submitted.
     */
    public function isNew(object $data): bool
    {
        $taskId = $data->taskId ?? $this->getTopicTask($data->topicId, [strtoupper(TaskState::ACTIVE)]);
        if (isset($taskId)) {
            $authorisation = $this->getAuthorisation();
            $participant_id = $data->participantId ?? null;
            if ($authorisation->isParticipant()) {
                $participant_id = $data->participantId ?? $authorisation->id;
            }
            $condition = [
                "task_id" => $taskId,
                "participant_id" => $participant_id,
                "keywords" => $data->keywords ?? null,
                "description" => $data->description ?? null,
                "image" => $data->image ?? null,
                "link" => $data->link ?? null
            ];
            foreach ($condition as $key => $value) {
                if (is_null($value)) {
                    $condition["$key is"] = $value;
                    unset($condition[$key]);
                }
            }

            $query = $this->queryFactory->newSelect("idea");
            $query->select(["id"])
                ->andWhere($condition);
            return ($query->execute()->rowCount() == 0);
        }
        return false;
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        $input = [
            "id" => $data->id ?? null,
            "description" => $data->description ?? null,
            "keywords" => $data->keywords ?? null,
            "link" => $data->link ?? null,
            "state" => $data->state ?? null,
            "timestamp" => $data->timestamp ?? null,
            "task_id" => $data->taskId ?? null,
            "participant_id" => $data->participantId ?? null,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null,
            "order" => $data->order ?? 0
        ];
        if (!$input["participant_id"]) {
            unset($input["participant_id"]);
        }
        return $input;
    }

    /**
     * Include dependent data.
     * @param string $oldId Old table primary key
     * @param string $newId Old table primary key
     * @return void
     */
    protected function cloneDependencies(
        string $oldId,
        string $newId
    ): void
    {
        $newTaskId = null;
        $newIdeaRow = $this->queryFactory->newSelect("idea")
            ->select([
                "task_id"
            ])
            ->andWhere([
                "id" => $newId,
            ])
            ->execute()
            ->fetchAll("assoc");
        if (is_array($newIdeaRow) and sizeof($newIdeaRow) == 1) {
            $reader = new ArrayReader($newIdeaRow[0]);
            $newTaskId = $reader->findString("task_id");
        }

        $rows = $this->queryFactory->newSelect("hierarchy")
            ->select([
            "hierarchy.sub_idea_id",
            "hierarchy.order"
            ])
            ->innerJoin("idea", ["idea.id = hierarchy.sub_idea_id"])
            ->whereNull("idea.participant_id")
            ->andWhere([
                "hierarchy.category_idea_id" => $oldId,
            ])
            ->order(["hierarchy.order"])
            ->execute()
            ->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            foreach ($rows as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $oldSubId = $reader->findString("sub_idea_id");
                if ($oldSubId) {
                    $order = $reader->findInt("order");
                    $newSubId = $this->clone($oldSubId, $newTaskId);

                    $this->queryFactory->newInsert(
                        "hierarchy",
                        [
                            "category_idea_id" => $newId,
                            "sub_idea_id" => $newSubId,
                            "order" => $order
                        ])
                        ->execute()
                        ->rowCount();
                }
            }
        }
    }

    /**
     * List of columns to be cloned
     * @return array<string> The array
     */
    protected function cloneColumns(): array
    {
        return [
            "state",
            "keywords",
            "description",
            "image",
            "image_timestamp",
            "link",
            "parameter",
            "order"
        ];
    }
}
