<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Service to read all open tasks for the active participant
 */
class ParticipantTaskReader
{
    use BaseServiceTrait;
    use ParticipantServiceTrait;

}
