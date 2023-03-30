<?php

namespace App\Domain\TaskParticipantIteration\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all session roles for one session
 */
class TaskParticipantIterationReader
{
    use ServiceReaderTrait;
    use TaskParticipantIterationTrait;
}
