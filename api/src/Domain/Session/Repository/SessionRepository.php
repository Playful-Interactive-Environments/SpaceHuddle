<?php

namespace App\Domain\Session\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\KeyGeneratorTrait;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Topic\Repository\TopicRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\QueryFactory;
use App\Domain\Session\Data\SessionData;
use DomainException;

/**
 * Repository
 */
class SessionRepository implements RepositoryInterface
{
    use KeyGeneratorTrait;
    use RepositoryTrait {
        RepositoryTrait::insert as private genericInsert;
    }

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "session",
            SessionData::class,
            "user_id",
            UserRepository::class
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        $authorisation = $this->getAuthorisation();
        $conditions = [
            "session_id" => $id,
            "user_id" => $authorisation->id,
            "user_type" => $authorisation->type
        ];
        $role = $this->getAuthorisationRoleFromCondition(
            $id,
            $conditions,
            "session_permission",
            false
        );

        if ($authorisation->isParticipant() and $role == SessionRoleType::PARTICIPANT) {
            $result = $this->get([
                "session.id" => $id
            ]);
            if (!is_object($result)) {
                return SessionRoleType::EXPIRED;
            }
        }

        return $role;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return SessionData|array<SessionData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = []): null|SessionData|array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [
            "session_permission.user_id" => $authorisation->id,
            "session_permission.user_type" => $authorisation->type
        ];

        if ($authorisation->isParticipant()) {
            array_push($conditions, "session.expiration_date >= current_timestamp()");
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["session.*", "session_permission.role"])
            ->innerJoin("session_permission", "session_permission.session_id = session.id")
            ->andWhere($authorisation_conditions)
            ->andWhere($conditions);

        return $this->fetchAll($query);
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return SessionData|null The result entity.
     * @throws GenericException
     */
    public function getById(string $id): ?SessionData
    {
        $result = $this->get([
            "session.id" => $id
        ]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @return array<SessionData> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array
    {
        $result = $this->get([
            #"session_permission.user_state" => "active"
        ]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Insert session row.
     * @param object $data The session data
     * @return object|null The new session
     * @throws GenericException
     */
    public function insert(object $data): ?object
    {
        $data->connectionKey = $this->generateNewConnectionKey("connection_key");
        return $this->genericInsert($data);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter)) {
            $this->queryFactory->newInsert("session_role", [
                "session_id" => $id,
                "user_id" => $parameter->userId,
                "role" => strtoupper(SessionRoleType::MODERATOR)
            ])->execute();
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     * @throws GenericException
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $participant = new ParticipantRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $participantId = $resultItem["id"];
                $participant->deleteById($participantId);
            }
        }

        $query = $this->queryFactory->newSelect("topic");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $topic = new TopicRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $topicId = $resultItem["id"];
                $topic->deleteById($topicId);
            }
        }

        $this->queryFactory->newDelete("resource")
            ->andWhere(["session_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("session_role")
            ->andWhere(["session_id" => $id])
            ->execute();
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "title" => $data->title ?? null,
            "description" => $data->description ?? null,
            "connection_key" => $data->connectionKey ?? null,
            "max_participants" => $data->maxParticipants ?? null,
            "expiration_date" => $data->expirationDate ?? null,
            "public_screen_module_id" => $data->publicScreenModuleId ?? null
        ];
    }
}
