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
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|object|array;

    /**
     * Get entity by ID.
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
     * @return object|null The new created entity
     * @throws GenericException
     */
    public function insert(object $data): ?object;

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
    public function deleteById(string $id): void;

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
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string;

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(AuthorisationData $authorisation, ?string $id): ?string;
}
