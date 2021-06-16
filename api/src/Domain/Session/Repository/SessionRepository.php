<?php

namespace App\Domain\Session\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\KeyGeneratorTrait;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Participant\Repository\ParticipantRepository;
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
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        if (is_null($id)) {
            return strtoupper(SessionRoleType::MODERATOR);
        }
        $query = $this->queryFactory->newSelect("session_permission");
        $query->select(["role"])
            ->andWhere([
                "session_id" => $id,
                "user_id" => $authorisation->id,
                "user_type" => $authorisation->type
            ]);

        $statement = $query->execute();
        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $result = $statement->fetch("assoc");
            return strtoupper($result["role"]);
        }
        return null;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @return SessionData|array<SessionData>|null The result entity(s).
     * @throws GenericException
     */
    public function getAuthorised(array $conditions, AuthorisationData $authorisation): null|SessionData|array
    {
        $authorisation_conditions = [
            "session_permission.user_id" => $authorisation->id,
            "session_permission.user_type" => $authorisation->type
        ];

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["session.*", "session_permission.role"])
            ->innerJoin("session_permission", "session_permission.session_id = session.id")
            ->andWhere($authorisation_conditions)
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
     * @param string $id The entity ID.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @return SessionData|null The result entity.
     * @throws GenericException
     */
    public function getByIdAuthorised(string $id, AuthorisationData $authorisation): ?SessionData
    {
        $result = $this->getAuthorised([
            "session.id" => $id
        ], $authorisation);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @return array<SessionData> The result entity list.
     * @throws GenericException
     */
    public function getAllAuthorised(string $parentId, AuthorisationData $authorisation): array
    {
        $result = $this->getAuthorised([
            #"session_permission.user_state" => "active"
        ], $authorisation);
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
            //TODO: Implement an equivalent for getInstance
            #$topic = new TopicRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $topicId = $resultItem["id"];
                #$topic->deleteById($topicId);
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
            "connection_key" => $data->connectionKey ?? null,
            "max_participants" => $data->maxParticipants ?? null,
            "expiration_date" => $data->expirationDate ?? null,
            "public_screen_module_id" => $data->publicScreenModuleId ?? null
        ];
    }
}
