<?php

namespace App\Domain\TaskParticipantIteration\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update session role service.
 */
class TaskParticipantIterationUpdater
{
    use ServiceUpdaterTrait;
    use TaskParticipantIterationTrait;

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
