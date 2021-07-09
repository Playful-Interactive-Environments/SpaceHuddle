<?php

namespace App\Domain\Resource\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific resource service
 */
class ResourceSingleReader
{
    use ServiceSingleReaderTrait;
    use ResourceServiceTrait;
}
