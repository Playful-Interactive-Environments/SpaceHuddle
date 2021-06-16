<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the read database entries functionality.
 */
trait SelectTrait
{
    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|object|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions);

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            if (sizeof($rows) === 1) {
                return $this->createResultClass($rows[0]);
            } else {
                $result = [];
                foreach ($rows as $resultItem) {
                    array_push($result, $this->createResultClass($resultItem));
                }
                return $result;
            }
        }
        return null;
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     * @throws GenericException
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
     * @throws GenericException
     */
    public function getById(string $id): ?object
    {
        $result = $this->get(["id" => $id]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->getEntityName() not found");
        }
        return $result;
    }
}
