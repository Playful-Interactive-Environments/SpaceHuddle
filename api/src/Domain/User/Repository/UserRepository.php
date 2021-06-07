<?php

namespace App\Domain\User\Repository;

use App\Domain\Base\AbstractData;
use App\Domain\Base\AbstractRepository;
use App\Domain\User\Data\UserData;
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
    public function __construct(QueryFactory $queryFactory)
    {
        parent::__construct($queryFactory, "user", UserData::class);
    }

    /**
     * Insert user row.
     * @param object $data The user data
     * @return AbstractData The new ID
     */
    public function insert(object $data): AbstractData
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
    public function getUserByName(string $username): AbstractData
    {
        return $this->get(["username" => $username]);
    }

    /**
     * Update user row.
     * @param object|array $data The user
     * @return AbstractData The updated user.
     */
    public function update(object|array $data): AbstractData
    {
        // Updating the password is another use case
        unset($data->password);
        return parent::update($data);
    }

    /**
     * Update user row.
     * @param object $data The user
     * @return AbstractData
     */
    public function updatePassword(object $data): AbstractData
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
     * Convert to array.
     * @param object $data The entity data
     * @return array The array
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
