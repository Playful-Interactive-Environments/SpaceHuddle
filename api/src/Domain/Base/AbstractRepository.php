<?php

namespace App\Domain\Base;

use App\Factory\QueryFactory;
use DomainException;

/**
 * Description of the common repository functionality.
 * @package App\Domain\Service
 */
abstract class AbstractRepository
{
    protected QueryFactory $queryFactory;
    protected ?string $entityName;
    protected ?string $resultClass;

    /**
     * Get private properties
     * @param string $name Private property name
     * @return mixed Property value
     */
    public function __get(string $name): mixed
    {
        $method = "get" . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return null;
        }
    }

    /**
     * Get the entity table name.
     * @return string|null entity table name
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     * @param string|null $entityName Name of the database table
     * @param string|null $resultClass Name of the result class
     */
    public function __construct(QueryFactory $queryFactory, ?string $entityName = null, ?string $resultClass = null)
    {
        $this->queryFactory = $queryFactory;
        $this->entityName = $entityName;
        $this->resultClass = $resultClass;
    }

    /**
     * Checks if the basic generic parameters have been set.
     * @return bool Returns true if the basic generic parameters have been set.
     */
    protected function genericTableParameterSet(): bool
    {
        return (
            isset($this->entityName) and
            isset($this->resultClass)
        );
    }

    /**
     * Insert entity row.
     * @param object $data The data to be inserted
     * @return AbstractData|null The new created entity
     */
    public function insert(object $data): ?AbstractData
    {
        if ($this->genericTableParameterSet()) {
            $data->id = uuid_create();
            $row = $this->toRow($data);

            $this->queryFactory->newInsert($this->entityName, $row)
                ->execute();

            return $this->getById($data->id);
        }
        return null;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return AbstractData|null The result entity.
     */
    public function get(array $conditions = []): ?AbstractData
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"]);
            $query->andWhere($conditions);

            $row = $query->execute()->fetch("assoc");

            if (!$row) {
                throw new DomainException("Entity $this->entityName not found");
            }

            return new $this->resultClass($row);
        }
        return null;
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return AbstractData|null The result entity.
     */
    public function getById(string $id): ?AbstractData
    {
        return $this->get(["id" => $id]);
    }

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return AbstractData|null The result entity.
     */
    public function update(object|array $data): ?AbstractData
    {
        if ($this->genericTableParameterSet()) {
            if (!is_array($data)) {
                $data = $this->toRow($data);
            }

            $id = $data["id"];
            unset($data["id"]);

            $this->queryFactory->newUpdate($this->entityName, $data)
                ->andWhere(["id" => $id])
                ->execute();

            return $this->getById($id);
        }
        return null;
    }

    /**
     * Check entity.
     * @param array $conditions The WHERE conditions to add with AND
     * @return bool True if exists
     */
    public function exists(array $conditions = []): bool
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"]);
            $query->andWhere($conditions);

            return (bool)$query->execute()->fetch("assoc");
        }
        return false;
    }

    /**
     * Check entity ID.
     * @param string $id The entity ID.
     * @return bool True if exists
     */
    public function existsId(string $id): bool
    {
        return self::exists(["id" => $id]);
    }

    /**
     * Encrypts the text
     * @param string $text Text to be encrypted.
     * @return string The hashed text.
     */
    protected static function encryptText(string $text): string
    {
        // Hash text
        return password_hash($text, PASSWORD_DEFAULT);
    }

    /**
     * Checks whether the encrypted text for the specified entity is correct.
     * @param array $conditions The WHERE conditions to add with AND
     * @param string $text The original text
     * @param string $textColumnName Database column name of the column to be encrypted.
     * @return bool True if text matches.
     */
    public function checkEncryptText(array $conditions, string $text, string $textColumnName = "password"): bool
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(
                [
                    $textColumnName
                ]
            );

            $query->andWhere($conditions);

            $row = $query->execute()->fetch("assoc");

            if ($row) {
                $hash = $row[$textColumnName];
                return password_verify($text, $hash);
            }
        }

        return false;
    }

    /**
     * Generates a random string.
     * @param int $length Length of the result string.
     * @param bool $caseSensitive Use lower case and upper case letters.
     * @return string Returns random string.
     */
    protected static function keygen(int $length = 10, bool $caseSensitive = true): string
    {
        $key = "";
        list($uSec, $sec) = explode(" ", microtime());
        mt_srand((int)((float)$sec + ((float)$uSec * 100000)));

        $inputs = array_merge(range("z", "a"), range(0, 9), range("A", "Z"));
        if (!$caseSensitive) {
            $inputs = array_merge(range(0, 9), range("A", "Z"));
        }

        for ($i = 0; $i < $length; $i++) {
            $index = mt_rand(0, count($inputs) - 1);
            $key .= $inputs[$index];
        }
        return $key;
    }

    /**
     * Generate a new connection key.
     * @param string $keyColumnName Database column name of the column to be encrypted.
     * @return string The connection key.
     */
    protected function generateNewConnectionKey(string $keyColumnName = "connection_key"): string
    {
        if ($this->genericTableParameterSet()) {
            $connectionKey = "";
            while (strlen($connectionKey) == 0) {
                $connectionKey = self::keygen(8, false);
                $query = $this->queryFactory->newSelect($this->entityName);
                $query->select("id")->andWhere([$keyColumnName => $connectionKey]);
                if ($query->execute()->fetch("assoc")) {
                    $connectionKey = "";
                }
            }
            return $connectionKey;
        }
        return self::keygen(8, false);
    }

    /**
     * Checks whether the encrypted text for the specified entity is correct.
     * @param string $id The entity ID.
     * @param string $text The original text.
     * @param string $textColumnName Database column name of the column to be encrypted.
     * @return bool True if text matches.
     */
    public function checkEncryptTextForId(string $id, string $text, string $textColumnName = "password"): bool
    {
        return self::checkEncryptText(["id" => $id], $text, $textColumnName);
    }

    /**
     * Delete entity row.
     * @param string $id The entity ID.
     * @return void
     */
    public function deleteById(string $id): void
    {
        if ($this->genericTableParameterSet()) {
            $this->queryFactory->newDelete($this->entityName)
                ->andWhere(["id" => $id])
                ->execute();
        }
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function toRow(object $data): array
    {
        return (array)$data;
    }
}
