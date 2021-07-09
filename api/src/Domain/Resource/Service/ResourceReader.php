<?php

namespace App\Domain\Resource\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all resources for one topic
 */
class ResourceReader
{
    use ServiceReaderTrait;
    use ResourceServiceTrait;
}
