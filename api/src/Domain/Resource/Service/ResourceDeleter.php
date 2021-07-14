<?php

namespace App\Domain\Resource\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete resource service.
 */
class ResourceDeleter
{
    use ServiceDeleterTrait;
    use ResourceServiceTrait;
}
