<?php

namespace App\Domain\TaskParticipantState\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all session roles for one session
 */
class TaskParticipantStateReader
{
    use ServiceReaderTrait;
    use TaskParticipantStateTrait;
}
