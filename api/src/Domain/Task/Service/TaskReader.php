<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all topics for one session
 */
class TaskReader
{
    use ServiceReaderTrait;
    use TaskServiceTrait;
}
