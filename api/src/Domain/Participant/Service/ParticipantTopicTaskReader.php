<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Service to read all open tasks for the specified topic for the active participant
 */
class ParticipantTopicTaskReader
{
    use BaseServiceTrait;
    use ParticipantServiceTrait;

}
