<?php

namespace App\Domain\Idea\Repository;

trait IdeaTableTrait
{
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
     * Determine the first active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     * @param string $topicId Topic ID
     * @param array $validStates Valid states
     * @return string|null First active brainstorming task that is assigned to the topic.
     * If no corresponding task has been created, NULL is returned.
     */
    public function getTopicTask(string $topicId, array $validStates): ?string
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

        $result = $query->execute()->fetch("assoc");
        if ($result) {
            return $result["id"];
        }
        return null;
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
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "description" => $data->description ?? null,
            "keywords" => $data->keywords ?? null,
            "image" => $data->image ?? null,
            "link" => $data->link ?? null,
            "state" => $data->state ?? null,
            "timestamp" => $data->timestamp ?? null,
            "task_id" => $data->taskId ?? null,
            "participant_id" => $data->participantId ?? null
        ];
    }
}
