<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific selection service
 */
class SelectionSingleReader
{
    use ServiceSingleReaderTrait;
    use SelectionServiceTrait;
}
