<?php


namespace App\Domain\Session\Repository;


use App\Domain\Service\Generator;
use App\Domain\User\Data\UserRole;
use App\Factory\QueryFactory;
use App\Domain\Session\Data\SessionData;
use Selective\ArrayReader\ArrayReader;

class SessionRepository
{
    private QueryFactory $queryFactory;

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
     * Check session id.
     *
     * @param string $sessionId The session id
     *
     * @return bool True if exists
     */
    public function existsSessionId(string $sessionId): bool
    {
        $query = $this->queryFactory->newSelect("session");
        $query->select("id")->andWhere(["id" => $sessionId]);

        return (bool)$query->execute()->fetch("assoc");
    }

    /**
     * Get user by id.
     *
     * @param string $sessionId The user id
     *
     * @return SessionData The session
     *@throws DomainException
     *
     */
    public function getSessionById(string $sessionId): SessionData
    {
        $query = $this->queryFactory->newSelect("session");
        $query->select(
            [
                "id",
                "title",
                "connection_key",
                "max_participants",
                "expiration_date",
                "creation_date",
                "public_screen_module_id"
            ]
        );

        $query->andWhere(["id" => $sessionId]);

        $row = $query->execute()->fetch("assoc");

        if (!$row) {
            throw new DomainException("Session not found: $sessionId");
        }

        return new SessionData($row);
    }

    /**
     * Insert user row.
     *
     * @param object $session The user data
     *
     * @return SessionData The new session
     */
    public function inserSession(string $userId, object $session): SessionData
    {
        $session->id = uuid_create();
        $session->connectionKey = $this->generateNewSessionKey();
        $row = $this->toRow($session);

        $this->queryFactory->newInsert("session", $row)
            ->execute();

        $this->queryFactory->newInsert("session_role", [
                "session_id" => $session->id,
                "user_id" => $userId,
                "role" => strtoupper(UserRole::MODERATOR)
            ])
            ->execute();

        return $this->getSessionById($session->id);
    }

    /**
     * Generate a new session key.
     * @return string The session key.
     */
    private function generateNewSessionKey(): string
    {
        $connectionKey = "";
        while (strlen($connectionKey) == 0) {
            $connectionKey = Generator::keygen(8, false);
            $query = $this->queryFactory->newSelect("session");
            $query->select("id")->andWhere(["connection_key" => $connectionKey]);

            #if (!(bool)$query->execute()->fetch("assoc")) {
            #    $connectionKey = "";
            #}
        }
        return $connectionKey;
    }

    /**
     * Convert to array.
     *
     * @param object $session The user data
     *
     * @return array The array
     */
    private function toRow(object $session): array
    {
        return [
            "id" => $session->id ?? null,
            "title" => $session->title ?? null,
            "connection_key" => $session->connectionKey ?? null,
            "max_participants" => $session->maxParticipants ?? null,
            "expiration_date" => $session->expirationDate ?? null,
            "public_screen_module_id" => $session->publicScreenModuleId ?? null
        ];
    }

}
