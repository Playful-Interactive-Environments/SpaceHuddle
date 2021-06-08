<?php


namespace App\Domain\Session\Repository;


use App\Domain\Base\AbstractData;
use App\Domain\Base\AbstractRepository;
use App\Domain\User\Data\UserRole;
use App\Factory\QueryFactory;
use App\Domain\Session\Data\SessionData;

/**
 * Repository
 */
class SessionRepository extends AbstractRepository
{
    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        parent::__construct($queryFactory, "session", SessionData::class);
    }

    /**
     * Insert session row.
     * @param object $data The session data
     * @param string|null $userId Authorised user
     * @return AbstractData|null The new session
     */
    public function insert(object $data, string $userId = null): ?AbstractData
    {
        $data->connectionKey = $this->generateNewConnectionKey("connection_key");
        $result = parent::insert($data);

        $id = $result->id;
        $this->queryFactory->newInsert("session_role", [
                "session_id" => $id,
                "user_id" => $userId,
                "role" => strtoupper(UserRole::MODERATOR)
            ])
            ->execute();

        return $this->getById($id);
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function toRow(object $data): array
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
