<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Participant delete service.
 */
class ParticipantDeleter
{
    use ServiceDeleterTrait;
    use ParticipantServiceTrait;

}
