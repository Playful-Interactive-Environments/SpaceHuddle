<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Service to read all tasks for one topic
 */
class TaskReaderDependent
{
    use ServiceSingleReaderTrait;
    use TaskServiceTrait;

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
        $id = $data["id"];

        // Fetch data from the database
        return $this->repository->getDependent($id);
    }
}
