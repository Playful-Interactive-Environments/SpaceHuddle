<?php

namespace App\Domain\TaskParticipantIterationStep\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update session role service.
 */
class TaskParticipantIterationStepUpdater
{
    use ServiceUpdaterTrait;
    use TaskParticipantIterationStepTrait;

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
