<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the create database entries functionality.
 */
trait InsertTrait
{
    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new created entity
     */
    public function insert(object $data, bool $insertDependencies = true): ?object
    {
        $data->id = uuid_create();

        $usedKeys = array_values($this->translateKeys((array)$data));
        $row = $this->formatDatabaseInput($data);
        $row = $this->unsetUnused($row, $usedKeys);

        $itemCount = $this->queryFactory->newInsert($this->getEntityName(), $row)
            ->execute()->rowCount();

        if ($insertDependencies && $itemCount > 0 and array_key_exists("id", $row)) {
            $this->insertDependencies($data->id, $data);
        }

        return $this->getById($data->id);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return (array)$data;
    }
}
