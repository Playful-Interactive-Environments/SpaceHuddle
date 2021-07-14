<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class CategorySingleReader
{
    use ServiceSingleReaderTrait;
    use CategoryServiceTrait;
}
