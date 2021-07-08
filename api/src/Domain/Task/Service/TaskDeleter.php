<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete task service.
 */
class TaskDeleter
{
    use ServiceDeleterTrait;
    use TaskServiceTrait;
}
