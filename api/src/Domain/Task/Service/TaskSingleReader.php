<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific task service
 */
class TaskSingleReader
{
    use ServiceSingleReaderTrait;
    use TaskServiceTrait;
}
