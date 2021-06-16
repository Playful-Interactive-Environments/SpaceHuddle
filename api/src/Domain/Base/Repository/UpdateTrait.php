<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the update database entries functionality.
 */
trait UpdateTrait
{
    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        if (!is_array($data)) {
            $data = $this->formatDatabaseInput($data);
        }
        $data = $this->unsetUnused($data);

        $id = $data["id"];
        unset($data["id"]);

        $this->queryFactory->newUpdate($this->getEntityName(), $data)
            ->andWhere(["id" => $id])
            ->execute();

        return $this->getById($id);
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

    /**
     * Unset unused entity properties.
     * @param array $data Total entity properties.
     * @return array Only occupied entity properties.
     */
    protected function unsetUnused(array $data): array
    {
        $keys = array_keys($data);
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
