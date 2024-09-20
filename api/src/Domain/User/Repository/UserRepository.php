<?php

namespace App\Domain\User\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\EncryptTrait;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Data\UserAdminData;
use App\Domain\User\Data\UserData;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\User\Type\UserRoleType;
use App\Factory\QueryFactory;

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
     * @param string|null $id Primary key to be checked.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(?string $id, string | null $detailEntity = null): ?string
    {
        $authorisation = $this->getAuthorisation();
        return SessionRoleType::mapAuthorisationType($authorisation->type);
    }

    /**
     * Insert user row.
     * @param object $data The user data
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new user
     * @throws GenericException
     */
    public function insert(object $data, bool $insertDependencies = true): object|null
    {
        $data->password = self::encryptText($data->password);
        return $this->genericInsert($data, $insertDependencies);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        $sessionRepository = new SessionRepository($this->queryFactory);
        $list = $sessionRepository->getPublicSessions(false);
        foreach ($list as $item) {
            $data = (object)((array)$item);
            $data->templateId = $item->id;
            unset($data->id);
            unset($data->connectionKey);
            unset($data->creationDate);
            unset($data->publicScreenModuleId);
            unset($data->role);
            unset($data->visibility);
            unset($data->topicCount);
            unset($data->taskCount);
            unset($data->allowAnonymous);
            $sessionRepository->setAuthorisation(AuthorisationData::instanceFromUserId($id));
            $sessionRepository->createFromTemplate($data);
        }
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
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<UserAdminData> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array
    {
        $query = $this->queryFactory->newSelect('user_state');
        $query->select(["*"]);

        $rows = $query->execute()->fetchAll("assoc");
        $result = [];
        foreach ($rows as $resultItem) {
            array_push($result, new UserAdminData($resultItem));
        }
        return $result;
    }

    /**
     * Is Admin.
     * @return bool Is Admin.
     * @throws GenericException
     */
    public function isAdmin(): bool {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isUser()) {
            $userData = $this->get(["id" => $authorisation->id]);
            return ($userData->role === UserRoleType::ADMIN);
        }
        return false;
    }

    /**
     * Confirm email address.
     * @param string $id The userId
     * @throws GenericException
     */
    public function confirmUser(string $id): void
    {
        $this->queryFactory->newUpdate("user", ["confirmed" => 1])
            ->andWhere(["id" => $id])
            ->execute();
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
     * Reset Password for username.
     * @param string $username The username
     * @param string $password New password
     * @return object|null The updated user.
     * @throws GenericException
     */
    public function resetPassword(string $username,  string $password): object|null
    {
        $this->queryFactory->newUpdate("user", ["password" => self::encryptText($password)])
            ->andWhere(["username" => $username])
            ->execute();
        return $this->getUserByName($username);
    }

    /**
     * Confirm email address.
     * @param string $username The username
     * @return object|null The updated user.
     * @throws GenericException
     */
    public function confirmEmail(string $username): object|null
    {
        $this->queryFactory->newUpdate("user", ["confirmed" => 1])
            ->andWhere(["username" => $username])
            ->execute();
        return $this->getUserByName($username);
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
     * Checks whether the specified user is confirmed.
     * @param string $username The username.
     * @return bool True if user is confirmed.
     */
    public function checkUsernameConfirmed(string $username): bool
    {
        return self::exists(["username" => $username, "confirmed" => 1]);
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
        $query->andWhere([
            "user_id" => $id,
            "role in" => [strtoupper(SessionRoleType::OWNER), strtoupper(SessionRoleType::MODERATOR)]
        ]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $session = new SessionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $sessionId = $resultItem["session_id"];

                $query = $this->queryFactory->newSelect("session_role");
                $query->select(["session_id"]);
                $query->andWhere([
                    "session_id" => $sessionId,
                    "role in" => [strtoupper(SessionRoleType::OWNER), strtoupper(SessionRoleType::MODERATOR)]
                ]);
                $itemCount = $query->execute()->rowCount();

                if ($itemCount === 1) {
                    $session->deleteById($sessionId);
                }
            }
        }

        $this->queryFactory->newDelete("session_role")
            ->andWhere(["user_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("tutorial")
            ->andWhere(["user_id" => $id])
            ->execute();
    }

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     */
    public function updateParameter(object|array $data): ?object
    {
        if (!is_array($data)) {
            $usedKeys = array_values($this->translateKeys((array)$data));
            $data = [
                "id" => $data->id ?? null,
                "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null
            ];
        }

        $id = $data["id"];
        unset($data["id"]);
        $data["modification_date"] = date('Y-m-d H:i:s');

        $this->queryFactory->newUpdate($this->getEntityName(), $data)
            ->andWhere(["id" => $id])
            ->execute();

        return $this->getById($id);
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
            "password" => $data->password,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null
        ];
    }
}
