<?php


namespace App\Domain\Base\Repository;

/**
 * Trait that provides the clone database entries functionality.
 */
trait CloneTrait
{
    /**
     * Clone entity row.
     * @param string $oldId Old table primary key
     * @param string | null $newParentId New parent key value to be inserted
     * @return string | null The new created entity id
     */
    public function clone(string $oldId, ?string $newParentId = null, bool $insertDependencies = true): ?string
    {
        $newId = $this->queryFactory->newClone(
            $this->getEntityName(),
            ["id" => $oldId],
            $this->cloneColumns(),
            $this->parentIdName,
            $newParentId
        );

        if ($insertDependencies && $newId) {
            $this->cloneDependencies($oldId, $newId);
        }
        return $newId;
    }

    /**
     * Include dependent data.
     * @param string $oldId Old table primary key
     * @param string $newId Old table primary key
     * @return void
     */
    protected function cloneDependencies(string $oldId, string $newId): void
    {
    }

    /**
     * List of columns to be cloned
     * @return array<string> The array
     */
    protected function cloneColumns(): array
    {
        return [];
    }
}
