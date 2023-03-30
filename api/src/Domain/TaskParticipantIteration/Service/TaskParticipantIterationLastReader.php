<?php

namespace App\Domain\TaskParticipantIteration\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Service to read all session roles for one session
 */
class TaskParticipantIterationLastReader
{
    use ServiceSingleReaderTrait;
    use TaskParticipantIterationTrait;

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
        if (array_key_exists("taskId", $data)) {
            $taskId = $data["taskId"];
            return $this->repository->getLast($taskId);
        }
        return null;
    }
}
