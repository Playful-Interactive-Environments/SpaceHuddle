<?php

namespace App\Domain\Module\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific module service
 */
class ModuleSingleReader
{
    use ServiceSingleReaderTrait;
    use ModuleServiceTrait;
}
