<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the update database entries functionality.
 */
trait UpdateTrait
{
    use CleanupTrait;

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        if (is_object($data)) {
            $this->updateDependencies($data->id, $data);
        }

        if (!is_array($data)) {
            $usedKeys = array_values($this->translateKeys((array)$data));
            $data = $this->formatDatabaseInput($data);
            $data = $this->unsetUnused($data, $usedKeys);
        }

        $id = $data["id"];
        unset($data["id"]);

        $this->queryFactory->newUpdate($this->getEntityName(), $data)
            ->andWhere(["id" => $id])
            ->execute();

        return $this->getById($id);
    }

    /**
     * Update dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function updateDependencies(string $id, array|object|null $parameter): void
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
