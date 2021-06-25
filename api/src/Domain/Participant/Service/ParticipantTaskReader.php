<?php

namespace App\Domain\Participant\Service;

use App\Data\AuthorisationData;
use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Service to read all open tasks for the active participant
 */
class ParticipantTaskReader
{
    use BaseServiceTrait;
    use ParticipantServiceTrait;

    /**
     * Functionality of the read all tasks for the logged-in participant service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        if ($authorisation->isLoggedIn()) {
            return $this->repository->getTasks($authorisation->id);
        }
        return null;
    }
}
