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
     * @return void
     * @throws GenericException
     */
    public function deleteById(string $id): void
    {
        $this->deleteDependencies($id);

        $this->queryFactory->newDelete($this->getEntityName())
            ->andWhere(["id" => $id])
            ->execute();
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
    }
}
