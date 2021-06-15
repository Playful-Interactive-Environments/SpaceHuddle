<?php

namespace App\Domain\Base\Repository;

use App\Factory\QueryFactory;

/**
 * Description of the common repository functionality.
 */
trait RepositoryTrait
{
    use GenericTrait;
    use AuthorisationRoleTrait;

    protected QueryFactory $queryFactory;

    /**
     * Basic setup for constructor.
     * @param QueryFactory $queryFactory The query factory
     * @param string|null $entityName Name of the database table
     * @param string|null $resultClass Name of the result class
     * @param string|null $parentIdName Column name of the parent ID.
     * @param string|null $parentRepository The parent repository class.
     * @return void
     */
    protected function setUp(
        QueryFactory $queryFactory,
        ?string $entityName = null,
        ?string $resultClass = null,
        ?string $parentIdName = null,
        ?string $parentRepository = null
    ): void {
        $this->queryFactory = $queryFactory;
        $this->setGenerics($queryFactory, $entityName, $resultClass, $parentIdName, $parentRepository);
    }

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

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @return object|null The new created entity
     * @throws GenericException
     */
    public function insert(object $data): ?object
    {
        $data->id = uuid_create();
        $row = $this->toRow($data);

        $itemCount = $this->queryFactory->newInsert($this->getEntityName(), $row)
            ->execute()->rowCount();

        if ($itemCount > 0 and array_key_exists("id", $row)) {
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
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        if (!is_array($data)) {
            $data = $this->toRow($data);
        }

        $id = $data["id"];
        unset($data["id"]);

        $this->queryFactory->newUpdate($this->getEntityName(), $data)
            ->andWhere(["id" => $id])
            ->execute();

        return $this->getById($id);
    }

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

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function toRow(object $data): array
    {
        return (array)$data;
    }
}
