<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class TaskSingleReader
{
    use ServiceSingleReaderTrait;
    use TaskServiceTrait;
}
