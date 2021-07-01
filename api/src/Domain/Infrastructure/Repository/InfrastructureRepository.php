<?php

namespace App\Domain\Infrastructure\Repository;

use App\Domain\Base\Repository\AuthorisationTrait;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\InstantiateTrait;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Topic\Repository\TopicRepository;
use App\Domain\User\Repository\UserRepository;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class InfrastructureRepository
{
    use AuthorisationTrait;
    use InstantiateTrait;

    protected QueryFactory $queryFactory;
    protected array $repositoryMapping = [
        "session" => SessionRepository::class,
        "topic" => TopicRepository::class,
        "task" => TaskRepository::class,
        "idea" => IdeaRepository::class,
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
     * @param string $entityName Name of the database table.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(string $entityName, ?string $id): ?string
    {
        $repository = $this->copy($this->repositoryMapping[$entityName]);
        return $repository->getAuthorisationRole($id);
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string $entityName Name of the database table.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(string $entityName, ?string $id): ?string
    {
        $repository = $this->copy($this->repositoryMapping[$entityName]);
        return $repository->getAuthorisationReadRole($id);
    }
}
