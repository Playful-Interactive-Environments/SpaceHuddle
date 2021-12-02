<?php

namespace App\Domain\Hierarchy\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class HierarchySingleReader
{
    use ServiceSingleReaderTrait;
    use HierarchyServiceTrait;
}
