<?php

namespace App\Domain\Tutorial\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Task\Type\TaskState;
use App\Domain\Tutorial\Data\TutorialData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\QueryFactory;
use function DI\add;

/**
 * Repository
 */
class TutorialRepository implements RepositoryInterface
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
            "tutorial",
            TutorialData::class,
            "user_id",
            UserRepository::class
        );
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|object|array
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isUser()) {
            $query = $this->queryFactory->newSelect($this->getEntityName());
            $query->select(["*"])
                ->andWhere(["user_id" => $authorisation->id])
                ->andWhere($conditions)
                ->order($sortConditions);

            return $this->fetchAll($query);
        }
        return [];
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     */
    public function getAll(string $parentId): array
    {
        $result = $this->get([]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new created entity
     * @throws GenericException
     */
    public function insert(object $data, bool $insertDependencies = true): ?object
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isUser()) {
            $condition = [
                "step" => $data->step,
                "type" => $data->type
            ];
            $result = $this->get($condition);
            if (is_null($result)) {
                $data->userId = $authorisation->id;
                $usedKeys = array_values($this->translateKeys((array)$data));
                $row = $this->formatDatabaseInput($data);
                $row = $this->unsetUnused($row, $usedKeys);
                $this->queryFactory
                    ->newInsert($this->getEntityName(), $row)
                    ->execute();

                return $this->get($condition);
            }
        }
        return null;
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        $result = [
            "user_id" => $data->userId ?? null,
            "step" => $data->step ?? null,
            "type" => $data->type ?? null,
            "order" => $data->order ?? 0
        ];

        return $result;
    }
}
