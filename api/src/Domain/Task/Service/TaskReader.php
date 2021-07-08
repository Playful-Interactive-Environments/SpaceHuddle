<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all tasks for one topic
 */
class TaskReader
{
    use ServiceReaderTrait;
    use TaskServiceTrait;
}
