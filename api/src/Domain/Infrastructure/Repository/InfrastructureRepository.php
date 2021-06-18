<?php

namespace App\Domain\Infrastructure\Repository;

use App\Data\AuthorisationData;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Repository\UserRepository;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class InfrastructureRepository
{
    protected QueryFactory $queryFactory;
    protected array $repositoryMapping = [
      "session" => SessionRepository::class,
      "user" => UserRepository::class,
      "participant" => ParticipantRepository::class
    ];

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
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
        $repository = new $this->repositoryMapping[$entityName]($this->queryFactory);
        return $repository->getAuthorisationRole($authorisation, $id);
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
        $repository = new $this->repositoryMapping[$entityName]($this->queryFactory);
        return $repository->getAuthorisationReadRole($authorisation, $id);
    }
}
