<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the delete database entries functionality.
 */
trait DeleteTrait
{
    /**
     * Delete entity row.
     * @param string $id The entity ID.
     * @param bool $returnParent If true, return parentId
     * @return string | null
     * @throws GenericException
     */
    public function deleteById(string $id, bool $returnParent = false): string | null
    {
        $this->deleteDependencies($id);
        $parentId = null;
        if ($returnParent) {
            $parentId = $this->getInvalidParentId($id);
        }

        $this->queryFactory->newDelete($this->getEntityName())
            ->andWhere(["id" => $id])
            ->execute();
        return $parentId;
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
    }

    /**
     * get parent for deleting object
     * @param string $id The entity ID.
     * @return string|null
     * @throws GenericException
     */
    protected function getInvalidParentId(string $id): string | null
    {
        if ($this->getParentIdName()) {
            $result = $this->queryFactory->newSelect($this->getEntityName())
                ->select([$this->getParentIdName()])
                ->andWhere(["id" => $id])
                ->execute()
                ->fetchAll();
            if (sizeof($result) > 0) {
                return $result[0][0];
            }
        }
        return null;
    }
}
