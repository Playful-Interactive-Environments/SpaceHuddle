<?php

namespace App\Domain\View\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class ViewTaskInputReader
{
    use ServiceSingleReaderTrait;
    use ViewServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateTaskRead($data);
    }

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
        $taskId = null;
        if (array_key_exists("taskId", $data)) {
            $taskId = $data["taskId"];
        }
        $order = null;
        if (array_key_exists("order", $data)) {
            $order = $data["order"];
        }
        $refId = null;
        if (array_key_exists("refId", $data)) {
            $refId = $data["refId"];
        }

        if (isset($taskId)) {
            // Fetch data from the database
            return $this->repository->getTaskInputDetails($taskId, $order, $refId);
        }
        return null;
    }
}
