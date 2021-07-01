<?php


namespace App\Domain\Base\Repository;

use Cake\Database\Query;
use DomainException;

/**
 * Trait that provides the read database entries functionality.
 */
trait SelectTrait
{
    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return object|array<object>|null The result entity(s).
     */
    public function get(array $conditions = []): null|object|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions);

        return $this->fetchAll($query);
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     */
    public function getAll(string $parentId): array
    {
        $result = $this->get([$this->getParentIdName() => $parentId]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return object|null The result entity.
     */
    public function getById(string $id): ?object
    {
        $result = $this->get(["id" => $id]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Fetches all entities from the database query and converts them into an object of the generic result class.
     * @param Query $query Database query.
     * @param string|null $resultClass Name of the result class
     * @return object|array|null The result entity(s).
     */
    protected function fetchAll(Query $query, ?string $resultClass = null) : null|object|array
    {
        if (is_null($resultClass)) {
            $resultClass = $this->getResultClass();
        }

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            if (sizeof($rows) === 1) {
                return new $resultClass($rows[0]);
            } else {
                $result = [];
                foreach ($rows as $resultItem) {
                    array_push($result, new $resultClass($resultItem));
                }
                return $result;
            }
        }
        return null;
    }
}
