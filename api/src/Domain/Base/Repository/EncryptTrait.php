<?php

namespace App\Domain\Base\Repository;

/**
 * Trait for encrypting and decrypting database entries.
 */
trait EncryptTrait
{

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
     * @return array|null Matching row if text matches.
     */
    public function checkEncryptTextAndGetRow(array $conditions, string $text, string $textColumnName = "password"): array|null
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(
            [
                $textColumnName
            ]
        );

        $query->andWhere($conditions);

        $rows = $query->execute()->fetchAll("assoc");

        foreach ($rows as $row) {
            $hash = $row[$textColumnName];
            $match = password_verify($text, $hash);
            if ($match) {
                return $row;
            }
        }

        return null;
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
        $row = $this->checkEncryptTextAndGetRow($conditions, $text, $textColumnName);
        return is_array($row);
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
}
