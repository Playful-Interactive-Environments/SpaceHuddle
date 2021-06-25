<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Service to read all open topics for the active participant
 */
class ParticipantTopicReader
{
    use BaseServiceTrait;
    use ParticipantServiceTrait;

}
