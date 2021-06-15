<?php

namespace App\Domain\Base\Repository;

use App\Factory\QueryFactory;

trait GenericTrait
{
    private ?string $entityName;
    private ?string $resultClass;
    private ?string $parentIdName;
    private ?RepositoryInterface $parentRepository;

    /**
     * Sets the generic parameters.
     * @param QueryFactory $queryFactory The query factory
     * @param string|null $entityName Name of the database table
     * @param string|null $resultClass Name of the result class
     * @param string|null $parentIdName Column name of the parent ID.
     * @param string|null $parentRepository The parent repository class.
     * @return void
     */
    protected function setGenerics(
        QueryFactory $queryFactory,
        ?string $entityName = null,
        ?string $resultClass = null,
        ?string $parentIdName = null,
        ?string $parentRepository = null
    ): void {
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
     * Get the entity table name.
     * @return string entity table name
     * @throws GenericException
     */
    public function getEntityName(): string
    {
        if (isset($this->entityName)) {
            return $this->entityName;
        } else {
            throw new GenericException("Entity name not set.");
        }
    }

    /**
     * Get the name of the result class.
     * @return string Name of the result class
     * @throws GenericException
     */
    protected function getResultClass(): string
    {
        if (isset($this->resultClass)) {
            return $this->resultClass;
        } else {
            throw new GenericException("Result class not set.");
        }
    }

    /**
     * Get the name of the result class.
     * @param array $data Content of the new entity.
     * @return object New instance of the result class
     * @throws GenericException
     */
    protected function createResultClass(array $data = []): object
    {
        if (isset($this->resultClass)) {
            return new $this->resultClass($data);
        } else {
            throw new GenericException("Result class not set.");
        }
    }

    /**
     * Get the column name of the parent ID.
     * @return string Column name of the parent ID.
     * @throws GenericException
     */
    protected function getParentIdName(): string
    {
        if (isset($this->parentIdName)) {
            return $this->parentIdName;
        } else {
            throw new GenericException("Column name of the parent ID not set.");
        }
    }

    /**
     * Get the parent repository.
     * @return RepositoryInterface parent repository
     * @throws GenericException
     */
    public function getParentRepository(): RepositoryInterface
    {
        if (isset($this->parentRepository)) {
            return $this->parentRepository;
        } else {
            throw new GenericException("Parent repository not set.");
        }
    }
}
