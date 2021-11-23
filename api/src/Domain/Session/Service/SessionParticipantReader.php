<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all participants for one session.
 */
class SessionParticipantReader
{
    use ServiceReaderTrait;
    use SessionServiceTrait;

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
            return $this->repository->getParticipants($sessionId);
        }
        return null;
    }
}
