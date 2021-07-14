<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that checks whether data exists.
 */
trait CheckTrait
{
    /**
     * Check entity.
     * @param array $conditions The WHERE conditions to add with AND
     * @return bool True if exists
     * @throws GenericException
     */
    public function exists(array $conditions = []): bool
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"]);
        $query->andWhere($conditions);

        return (bool)$query->execute()->fetch("assoc");
    }

    /**
     * Check entity ID.
     * @param string $id The entity ID.
     * @return bool True if exists
     * @throws GenericException
     */
    public function existsId(string $id): bool
    {
        return self::exists(["id" => $id]);
    }
}
