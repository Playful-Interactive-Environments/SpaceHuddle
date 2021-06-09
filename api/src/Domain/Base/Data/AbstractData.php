<?php

namespace App\Domain\Base\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Description of the common entity data functionality.
 * @package App\Domain\Base
 */
abstract class AbstractData
{
    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->initProperties($reader);
    }

    /**
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    abstract protected function initProperties(ArrayReader $reader) : void;
}
