<?php

namespace App\Domain\Module\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all modules for one task
 */
class ModuleReader
{
    use ServiceReaderTrait;
    use ModuleServiceTrait;
}
