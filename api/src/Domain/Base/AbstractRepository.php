<?php

namespace App\Domain\Base;

use App\Factory\QueryFactory;

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
     * @param $name Property name
     */
    public function __get($name) : mixed
    {
        $methode = "get".ucfirst($name);
        echo "search Methode: $methode";
        if (method_exists($this, $methode))
        {
            return $this->$methode();
        }
        else return null;
    }

    /**
     * get the entity table name
     * @return string|null entity table name
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
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
     *
     * @param object $data The data to be inserted
     *
     * @return AbstractData The new created entity
     */
    public function insert(object $data): AbstractData|null
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
     *
     * @param ?array $conditions The WHERE conditions to add with AND
     *
     * @throws DomainException
     *
     * @return AbstractData The result entity
     */
    public function get(array $conditions = null): AbstractData|null
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
     * Get entity by id.
     *
     * @param string $id The entity id
     *
     * @throws DomainException
     *
     * @return AbstractData The result entity
     */
    public function getById(string $id): AbstractData|null
    {
        return $this->get(["id" => $id]);
    }

    /**
     * Update entity row.
     *
     * @param AbstractData $data The entity to change
     *
     * @return void
     */
    public function update(object|array $data): AbstractData|null
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
     *
     * @param ?array $conditions The WHERE conditions to add with AND
     *
     * @return bool True if exists
     */
    public function exists(array $conditions = null): bool
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
     * Check entity id.
     *
     * @param string $id The entity id
     *
     * @return bool True if exists
     */
    public function existsId(string $id): bool
    {
        return self::exists(["id" => $id]);
    }

    /**
     * Encrypts the text
     * @param string $text Text to be encrypted.
     * @return string hashed text
     */
    protected static function encryptText(string $text) : string {
        // Hash text
        return password_hash($text, PASSWORD_DEFAULT);
    }

    /**
     * Checks whether the encrypt text for the specified entity is correct.
     *
     * @param array $conditions The WHERE conditions to add with AND
     * @param string $text The original text
     *
     * @return bool True if text matches.
     */
    public function checkEncryptText(array $conditions, string $text, string $textColumName = "password") : bool
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(
                [
                    $textColumName
                ]
            );

            $query->andWhere($conditions);

            $row = $query->execute()->fetch("assoc");

            if ($row) {
                $hash = $row[$textColumName];
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
        list($usec, $sec) = explode(" ", microtime());
        mt_srand((float)$sec + ((float)$usec * 100000));

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
                if ((bool)$query->execute()->fetch("assoc")) {
                    $connectionKey = "";
                }
            }
            return $connectionKey;
        }
        return self::keygen(8, false);
    }

    /**
     * Checks whether the encrypt text for the specified entity is correct.
     *
     * @param string $id The entity id
     * @param string $text The original text
     *
     * @return bool True if text matches.
     */
    public function checkEncryptTextForId(string $id, string $text, string $textColumName = "password") : bool
    {
        return self::checkEncryptText(["id" => $id], $text, $textColumName);
    }

    /**
     * Delete entity row.
     *
     * @param int $id The entity id
     *
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
     *
     * @param object $data The entity data
     *
     * @return array The array
     */
    protected function toRow(object $data): array
    {
        return (array)$data;
    }

}
