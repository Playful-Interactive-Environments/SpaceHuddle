<?php

namespace App\Domain\TaskParticipantState\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all participant states for one session
 */
class TaskParticipantStateFromSessionReader
{
    use ServiceReaderTrait;
    use TaskParticipantStateTrait;

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     * @throws GenericException
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        if (array_key_exists("sessionId", $data)) {
            $sessionId = $data["sessionId"];
            return $this->repository->getAllFromSession($sessionId);
        }
        return null;
    }
}
