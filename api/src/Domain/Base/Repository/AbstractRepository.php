<?php

namespace App\Domain\Base\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\AbstractData;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\QueryFactory;
use DomainException;

/**
 * Description of the common repository functionality.
 * @package App\Domain\Service
 */
abstract class AbstractRepository
{
    protected QueryFactory $queryFactory;
    protected ?string $entityName;
    protected ?string $resultClass;
    protected ?string $parentIdName;
    protected ?AbstractRepository $parentRepository;

    /**
     * Get private properties
     * @param string $name Private property name
     * @return mixed Property value
     */
    public function __get(string $name): mixed
    {
        $method = "get" . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return null;
        }
    }

    /**
     * Get the entity table name.
     * @return string|null entity table name
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * Get the parent repository.
     * @return AbstractRepository|null parent repository
     */
    public function getParentRepository(): ?AbstractRepository
    {
        return $this->parentRepository;
    }

    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     * @param string|null $entityName Name of the database table
     * @param string|null $resultClass Name of the result class
     * @param string|null $parentIdName Column name of the parent ID.
     * @param string|null $parentRepository The parent repository class.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ?string $entityName = null,
        ?string $resultClass = null,
        ?string $parentIdName = null,
        ?string $parentRepository = null
    ) {
        $this->queryFactory = $queryFactory;
        $this->entityName = $entityName;
        $this->resultClass = $resultClass;
        $this->parentIdName = $parentIdName;
        if (isset($parentRepository)) {
            $this->parentRepository = new $parentRepository($queryFactory);
        } else {
            $this->parentRepository = null;
        }
    }

    /**
     * Checks if all generic parameters have been set.
     * @return bool Returns true if all generic parameters have been set.
     */
    protected function allGenericParameterSet(): bool
    {
        return (
            $this->genericTableParameterSet() and
            isset($this->parentIdName) and
            isset($this->parentRepository)
        );
    }

    /**
     * Checks if the basic generic parameters have been set.
     * @return bool Returns true if the basic generic parameters have been set.
     */
    protected function genericTableParameterSet(): bool
    {
        return (
            isset($this->entityName) and
            isset($this->resultClass)
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        if (is_null($id)) {
            return SessionRoleType::mapAuthorisationType($authorisation->type);
        } else {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"])
                ->andWhere(["id" => $id]);
            $statement = $query->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $parentId = $statement->fetch("assoc")[$this->parentIdName];
                return $this->parentRepository->getAuthorisationRole($authorisation, $parentId);
            }
        }

        return null;
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationReadRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        return $this->getAuthorisationRole($authorisation, $id);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return AbstractData|array<AbstractData>|null The result entity(s).
     */
    public function get(array $conditions = []): null|AbstractData|array
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"])
                ->andWhere($conditions);

            $rows = $query->execute()->fetchAll("assoc");
            if (is_array($rows) and sizeof($rows) > 0) {
                if (sizeof($rows) === 1) {
                    return new $this->resultClass($rows[0]);
                } else {
                    $result = [];
                    foreach ($rows as $resultItem) {
                        array_push($result, new $this->resultClass($resultItem));
                    }
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @return array<AbstractData> The result entity list.
     */
    public function getAll(string $parentId): array
    {
        if ($this->allGenericParameterSet()) {
            $result = $this->get([$this->parentIdName => $parentId]);
            if (is_array($result)) {
                return $result;
            } elseif (isset($result)) {
                return [$result];
            }
        }
        return [];
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return AbstractData|null The result entity.
     */
    public function getById(string $id): ?AbstractData
    {
        $result = $this->get(["id" => $id]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @return AbstractData|null The new created entity
     */
    public function insert(object $data): ?AbstractData
    {
        if ($this->genericTableParameterSet()) {
            $data->id = uuid_create();
            $row = $this->toRow($data);

            $itemCount = $this->queryFactory->newInsert($this->entityName, $row)
                ->execute()->rowCount();

            if ($itemCount > 0 and array_key_exists("id", $row)) {
                $this->insertDependencies($data->id, $data);
            }

            return $this->getById($data->id);
        }
        return null;
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
     * @return AbstractData|null The result entity.
     */
    public function update(object|array $data): ?AbstractData
    {
        if ($this->genericTableParameterSet()) {
            if (!is_array($data)) {
                $data = $this->toRow($data);
            }

            $id = $data["id"];
            unset($data["id"]);

            $this->queryFactory->newUpdate($this->entityName, $data)
                ->andWhere(["id" => $id])
                ->execute();

            return $this->getById($id);
        }
        return null;
    }

    /**
     * Check entity.
     * @param array $conditions The WHERE conditions to add with AND
     * @return bool True if exists
     */
    public function exists(array $conditions = []): bool
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"]);
            $query->andWhere($conditions);

            return (bool)$query->execute()->fetch("assoc");
        }
        return false;
    }

    /**
     * Check entity ID.
     * @param string $id The entity ID.
     * @return bool True if exists
     */
    public function existsId(string $id): bool
    {
        return self::exists(["id" => $id]);
    }

    /**
     * Delete entity row.
     * @param string $id The entity ID.
     * @return void
     */
    public function deleteById(string $id): void
    {
        $this->deleteDependencies($id);

        if ($this->genericTableParameterSet()) {
            $this->queryFactory->newDelete($this->entityName)
                ->andWhere(["id" => $id])
                ->execute();
        }
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
