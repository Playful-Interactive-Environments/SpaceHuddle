<?php

namespace App\Domain\Base\Repository;

/**
 * Trait that provides the Get functionality for private properties.
 */
trait MagicPropertiesTrait
{
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
}
