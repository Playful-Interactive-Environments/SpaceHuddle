<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read ale topics for one session
 * @package App\Domain\Session\Service
 */
class TaskReader
{
    use ServiceReaderTrait;
    use TaskServiceTrait;
}
