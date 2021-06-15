<?php

namespace App\Domain\Base\Repository;

trait KeyGeneratorTrait
{
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
     * @param string $keyColumnName Database column name of the column in which the generated key is to be stored.
     * @param string $keyPrefix Text preceding the generated key.
     * @return string The connection key.
     */
    protected function generateNewConnectionKey(
        string $keyColumnName = "connection_key",
        string $keyPrefix = ""
    ): string {
        $connectionKey = "";
        while (strlen($connectionKey) == 0) {
            $connectionKey = $keyPrefix . self::keygen(8, false);
            $query = $this->queryFactory->newSelect($this->getEntityName());
            $query->select("id")->andWhere([$keyColumnName => $connectionKey]);
            if ($query->execute()->fetch("assoc")) {
                $connectionKey = "";
            }
        }
        return $connectionKey;
    }
}
