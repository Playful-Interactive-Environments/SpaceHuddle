<?php

namespace App\Domain\TaskParticipantState\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update session role service.
 */
class TaskParticipantStateUpdater
{
    use ServiceUpdaterTrait;
    use TaskParticipantStateTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateUpdate($data);
    }
}
