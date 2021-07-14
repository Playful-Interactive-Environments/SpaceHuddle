<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\ServiceUpdateMessageTrait;

/**
 * Update participant service.
 */
class ParticipantUpdater
{
    use ServiceUpdateMessageTrait;
    use ParticipantServiceTrait;
}
