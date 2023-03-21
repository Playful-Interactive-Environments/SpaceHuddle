<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read result of the voting for one parent idea.
 */
class VoteParentResultDetailReader
{
    use ServiceReaderTrait;
    use VoteServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateRead($data);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        if (array_key_exists("taskId", $data)) {
            $taskId = $data["taskId"];
            // Fetch data from the database
            return $this->repository->getParentResultDetails($taskId);
        }
        return null;
    }
}
