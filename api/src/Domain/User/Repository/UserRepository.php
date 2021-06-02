<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Data\UserData;
use App\Factory\QueryFactory;
use Cake\Chronos\Chronos;
use DomainException;

/**
 * Repository.
 */
final class UserRepository
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
     * Insert user row.
     *
     * @param object $user The user data
     *
     * @return UserData The new ID
     */
    public function insertUser(object $user): UserData
    {
        $user->id = uuid_create();
        $user->password = self::encryptPassword($user->password);
        $row = $this->toRow($user);

        $this->queryFactory->newInsert("user", $row)
            ->execute();

        return $this->getUserById($user->id);
    }

    /**
     * Encrypts the password
     * @param string $password Password to be encrypted.
     * @return string hashed password
     */
    private static function encryptPassword(string $password) : string {
        // Hash password
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Get user by id.
     *
     * @param string $userId The user id
     *
     * @throws DomainException
     *
     * @return UserData The user
     */
    public function getUserById(string $userId): UserData
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select(
            [
                "id",
                "username"
            ]
        );

        $query->andWhere(["id" => $userId]);

        $row = $query->execute()->fetch("assoc");

        if (!$row) {
            throw new DomainException("User not found: $userId");
        }

        return new UserData($row);
    }

    /**
     * Get user by username.
     *
     * @param string $username The user name
     *
     * @throws DomainException
     *
     * @return UserData The user
     */
    public function getUserByName(string $username): UserData
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select(
            [
                "id",
                "username"
            ]
        );

        $query->andWhere(["username" => $username]);

        $row = $query->execute()->fetch("assoc");

        if (!$row) {
            throw new DomainException("User not found: $username");
        }

        return new UserData($row);
    }

    /**
     * Update user row.
     *
     * @param UserData $user The user
     *
     * @return void
     */
    public function updateUser(object $user): UserData
    {
        $row = $this->toRow($user);

        // Updating the password is another use case
        unset($row["password"]);

        $row["updated_at"] = Chronos::now()->toDateTimeString();

        $this->queryFactory->newUpdate("user", $row)
            ->andWhere(["id" => $user->id])
            ->execute();

        return $this->getUserById($user->id);
    }

    /**
     * Update user row.
     *
     * @param UserData $user The user
     *
     * @return void
     */
    public function updatePassword(object $user): UserData
    {
        $user->password = self::encryptPassword($user->password);
        $row = [
            "password" => $user->password
        ];

        $this->queryFactory->newUpdate("user", $row)
            ->andWhere(["id" => $user->id])
            ->execute();

        return $this->getUserById($user->id);
    }

    /**
     * Check user id.
     *
     * @param string $userId The user id
     *
     * @return bool True if exists
     */
    public function existsUserId(string $userId): bool
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select("id")->andWhere(["id" => $userId]);

        return (bool)$query->execute()->fetch("assoc");
    }

    /**
     * Check user id.
     *
     * @param string $username The user name
     *
     * @return bool True if exists
     */
    public function existsUsername(string $username): bool
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select("id")->andWhere(["username" => $username]);

        return (bool)$query->execute()->fetch("assoc");
    }

    /**
     * Checks whether the password for the specified user is correct.
     *
     * @param string $username The user name
     * @param string $password The password
     *
     * @return bool True if password matches.
     */
    public function checkPasswordForUsername(string $username, string $password) : bool
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select(
            [
                "password"
            ]
        );

        $query->andWhere(["username" => $username]);

        $row = $query->execute()->fetch("assoc");

        if ($row) {
            $hash = $row["password"];
            return password_verify($password, $hash);
        }

        return false;
    }

    /**
     * Checks whether the password for the specified user is correct.
     *
     * @param string $username The user name
     * @param string $password The password
     *
     * @return bool True if password matches.
     */
    public function checkPasswordForUserId(string $userId, string $password) : bool
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select(
            [
                "password"
            ]
        );

        $query->andWhere(["id" => $userId]);

        $row = $query->execute()->fetch("assoc");

        if ($row) {
            $hash = $row["password"];
            return password_verify($password, $hash);
        }

        return false;
    }

    /**
     * Delete user row.
     *
     * @param int $userId The user id
     *
     * @return void
     */
    public function deleteUserById(int $userId): void
    {
        $this->queryFactory->newDelete("users")
            ->andWhere(["id" => $userId])
            ->execute();
    }

    /**
     * Convert to array.
     *
     * @param object $user The user data
     *
     * @return array The array
     */
    private function toRow(object $user): array
    {
        return [
            "id" => $user->id,
            "username" => $user->username,
            "password" => $user->password
        ];
    }
}
