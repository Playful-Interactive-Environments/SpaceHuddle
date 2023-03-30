<?php

namespace App\Domain\TaskParticipantIterationStep\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all session roles for one session
 */
class TaskParticipantIterationStepReader
{
    use ServiceReaderTrait;
    use TaskParticipantIterationStepTrait;
}
