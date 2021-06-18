<?php

namespace App\Domain\Infrastructure\Service;

use App\Data\AuthorisationData;
use App\Domain\Infrastructure\Repository\InfrastructureRepository;

/**
 * Service that checks the session role for the current route.
 * @package App\Domain\Infrastructure\Service
 */
class PermissionService
{
    protected InfrastructureRepository $repository;

    /**
     * The constructor.
     *
     * @param InfrastructureRepository $repository The repository
     */
    public function __construct(
        InfrastructureRepository $repository,
    ) {
        $this->repository = $repository;
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string $entityName Name of the database table.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, string $entityName, ?string $id): ?string
    {
        return $this->repository->getAuthorisationRole($authorisation, $entityName, $id);
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string $entityName Name of the database table.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationReadRole(AuthorisationData $authorisation, string $entityName, ?string $id): ?string
    {
        return $this->repository->getAuthorisationReadRole($authorisation, $entityName, $id);
    }
}
