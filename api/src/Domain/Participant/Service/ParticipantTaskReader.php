<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\BaseUrlServiceTrait;

/**
 * Service to read all open tasks for the active participant
 */
class ParticipantTaskReader
{
    use BaseUrlServiceTrait;
    use ParticipantServiceTrait;

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
        return $this->repository->getTasks($data["participantId"]);
    }
}
