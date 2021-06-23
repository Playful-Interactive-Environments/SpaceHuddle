<?php

namespace App\Domain\Topic\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Topic\Data\TopicData;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class TopicRepository implements RepositoryInterface
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
            "topic",
            TopicData::class,
            "session_id",
            SessionRepository::class
        );
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     * @throws GenericException
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"]);
        $query->andWhere(["topic_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            //TODO: Implement TaskRepository
            #$task = new TaskRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $taskId = $resultItem["id"];
                #$task->deleteById($taskId);
            }
        }
        $query = $this->queryFactory->newSelect("selection_group");
        $query->select(["id"]);
        $query->andWhere(["topic_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            //TODO: Implement SelectionRepository
            #$selection = new SelectionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $selectionId = $resultItem["id"];
                #$selection->deleteById($selectionId);
            }
        }
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
            "session_id" => $data->sessionId ?? null,
            "title" => $data->title ?? null,
            "description" => $data->description ?? null
        ];
    }
}
