<?php

namespace App\Domain\Base\Repository;

/**
 * Trait that provides functions to clean up unnecessary data.
 */
trait CleanupTrait
{

    /**
     * Convert a snake_case text to camelCase
     * @param string $text The snake_case text
     * @param bool $capitalizeFirstCharacter If true use PascalCase instead of camelCase
     * @return string camelCase text
     */
    protected function snakeCaseToCamelCase(string $text, bool $capitalizeFirstCharacter = false): string
    {
        $str = str_replace('_', '', ucwords($text, '_'));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }

    /**
     * Convert a camelCase text to snake_case
     * @param string $text The camelCase text
     * @return string snake_case text
     */
    protected function camelCaseToSnakeCase(string $text): string
    {
        $str = preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $text);
        return strtolower($str);
    }

    /**
     * Translate input keys to database column names.
     * @param array $data The entity data
     * @return array<string, string> The column name array
     */
    protected function translateKeys(array $data): array
    {
        $keys = array_keys($data);
        $translation = [];
        foreach ($keys as $key) {
            $translation[$key] = $this->camelCaseToSnakeCase($key);
        }
        return $translation;
    }

    /**
     * Unset unused entity properties.
     * @param array $data Total entity properties.
     * @return array Only occupied entity properties.
     */
    protected function unsetUnused(array $data, array $exceptionalKeys): array
    {
        $keys = array_keys($data);
        foreach ($keys as $key) {
            if (!isset($data[$key]) and !in_array($key, $exceptionalKeys)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
