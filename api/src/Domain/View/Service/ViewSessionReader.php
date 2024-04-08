<?php

namespace App\Domain\View\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all views for one session.
 */
class ViewSessionReader
{
    use ServiceReaderTrait;
    use ViewServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateSessionRead($data);
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
        if (array_key_exists("sessionId", $data)) {
            $sessionId = $data["sessionId"];
            // Fetch data from the database
            return $this->repository->getSessionList($sessionId);
        }
        return null;
    }
}
