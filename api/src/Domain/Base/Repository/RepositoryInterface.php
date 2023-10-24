<?php

namespace App\Domain\Base\Repository;

use App\Data\AuthorisationData;
use App\Factory\QueryFactory;

/**
 * Description of the common repository functionality.
 */
interface RepositoryInterface
{
    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(
        QueryFactory $queryFactory
    );

    /**
     * Sets the authorisation data for the repository.
     * @param AuthorisationData $authorisation Current authorisation data.
     * @return void
     */
    public function setAuthorisation(AuthorisationData $authorisation): void;

    /**
     * Gets the authorisation data from the repository.
     * @return AuthorisationData
     */
    public function getAuthorisation(): AuthorisationData;

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|object|array;

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<object> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array;

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function getById(string $id): ?object;

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new created entity
     * @throws GenericException
     */
    public function insert(object $data, bool $insertDependencies = true): ?object;

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object;

    /**
     * Check entity.
     * @param array $conditions The WHERE conditions to add with AND
     * @return bool True if exists
     * @throws GenericException
     */
    public function exists(array $conditions = []): bool;

    /**
     * Check entity ID.
     * @param string $id The entity ID.
     * @return bool True if exists
     * @throws GenericException
     */
    public function existsId(string $id): bool;

    /**
     * Delete entity row.
     * @param string $id The entity ID.
     * @return void
     * @throws GenericException
     */
    public function deleteById(string $id, bool $returnParent = false): string | null;

    /**
     * Get the entity table name.
     * @return string entity table name
     * @throws GenericException
     */
    public function getEntityName(): string;

    /**
     * Get the parent repository.
     * @return RepositoryInterface parent repository
     * @throws GenericException
     */
    public function getParentRepository(): RepositoryInterface;

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(?string $id, string | null $detailEntity = null): ?string;

    /**
     * Checks whether the user is authorised to delete the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationDeleteRole(?string $id): ?string;

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(?string $id): ?string;
}
