<?php

namespace App\Domain\User\Repository;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\AbstractRepository;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Data\UserData;
use App\Domain\User\Data\UserRole;
use App\Factory\QueryFactory;
use DomainException;

/**
 * Repository.
 */
final class UserRepository extends AbstractRepository
{
    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory,)
    {
        parent::__construct($queryFactory, "user", UserData::class);
    }

    /**
     * Insert user row.
     * @param object $data The user data
     * @return AbstractData|null The new user
     */
    public function insert(object $data): AbstractData|null
    {
        $data->password = self::encryptText($data->password);
        return parent::insert($data);
    }

    /**
     * Get user by username.
     * @param string $username The username.
     * @return AbstractData The user.
     * @throws DomainException
     *
     */
    public function getUserByName(string $username): AbstractData|null
    {
        return $this->get(["username" => $username]);
    }

    /**
     * Update user row.
     * @param object|array $data The user
     * @return AbstractData|null The updated user.
     */
    public function update(object|array $data): AbstractData|null
    {
        // Updating the password is another use case
        unset($data->password);
        return parent::update($data);
    }

    /**
     * Update user row.
     * @param object $data The user
     * @return AbstractData|null The updated user.
     */
    public function updatePassword(object $data): AbstractData|null
    {
        $row = [
            "id" => $data->id,
            "password" => self::encryptText($data->password)
        ];
        return parent::update($row);
    }

    /**
     * Check user ID.
     * @param string $username The username.
     * @return bool True if exists
     */
    public function existsUsername(string $username): bool
    {
        return self::exists(["username" => $username]);
    }

    /**
     * Checks whether the password for the specified user is correct.
     * @param string $username The username.
     * @param string $password The password
     * @return bool True if password matches.
     */
    public function checkPasswordForUsername(string $username, string $password): bool
    {
        return self::checkEncryptText(["username" => $username], $password);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("session_role");
        $query->select(["session_id"]);
        $query->andWhere(["user_id" => $id, "role" => strtoupper(UserRole::MODERATOR)]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            //TODO: Implement an equivalent for getInstance
            $session = new SessionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $sessionId = $resultItem["session_id"];

                $query = $this->queryFactory->newSelect("session_role");
                $query->select(["session_id"]);
                $query->andWhere(["session_id" => $sessionId, "role" => strtoupper(UserRole::MODERATOR)]);
                $itemCount = $query->execute()->rowCount();

                if ($itemCount === 1) {
                    $session->deleteById($sessionId);
                }
            }
        }

        $this->queryFactory->newDelete("session_role")
            ->andWhere(["user_id" => $id])
            ->execute();
    }


    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function toRow(object $data): array
    {
        return [
            "id" => $data->id,
            "username" => $data->username,
            "password" => $data->password
        ];
    }
}
