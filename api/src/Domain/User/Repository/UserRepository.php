<?php

namespace App\Domain\User\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\EncryptTrait;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Data\UserData;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\QueryFactory;
use DomainException;

/**
 * Repository.
 */
final class UserRepository implements RepositoryInterface
{
    use EncryptTrait;
    use RepositoryTrait {
        RepositoryTrait::insert as private genericInsert;
        RepositoryTrait::update as private genericUpdate;
    }

    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory,)
    {
        $this->setUp($queryFactory, "user", UserData::class);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        return SessionRoleType::mapAuthorisationType($authorisation->type);
    }

    /**
     * Insert user row.
     * @param object $data The user data
     * @return object|null The new user
     * @throws GenericException
     */
    public function insert(object $data): object|null
    {
        $data->password = self::encryptText($data->password);
        return $this->genericInsert($data);
    }

    /**
     * Get user by username.
     * @param string $username The username.
     * @return object|null The user.
     * @throws GenericException
     */
    public function getUserByName(string $username): object|null
    {
        return $this->get(["username" => $username]);
    }

    /**
     * Update user row.
     * @param object|array $data The user
     * @return object|null The updated user.
     * @throws GenericException
     */
    public function update(object|array $data): object|null
    {
        // Updating the password is another use case
        unset($data->password);
        return $this->genericUpdate($data);
    }

    /**
     * Update user row.
     * @param object $data The user
     * @return object|null The updated user.
     * @throws GenericException
     */
    public function updatePassword(object $data): object|null
    {
        $row = [
            "id" => $data->id,
            "password" => self::encryptText($data->password)
        ];
        return $this->genericUpdate($row);
    }

    /**
     * Check user ID.
     * @param string $username The username.
     * @return bool True if exists
     * @throws GenericException
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
     * @throws GenericException
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("session_role");
        $query->select(["session_id"]);
        $query->andWhere(["user_id" => $id, "role" => strtoupper(SessionRoleType::MODERATOR)]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            //TODO: Implement an equivalent for getInstance
            $session = new SessionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $sessionId = $resultItem["session_id"];

                $query = $this->queryFactory->newSelect("session_role");
                $query->select(["session_id"]);
                $query->andWhere(["session_id" => $sessionId, "role" => strtoupper(SessionRoleType::MODERATOR)]);
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
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id,
            "username" => $data->username,
            "password" => $data->password
        ];
    }
}
