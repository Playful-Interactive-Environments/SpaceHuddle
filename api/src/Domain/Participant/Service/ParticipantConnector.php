<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;

/**
 * Participant connect service.
 */
class ParticipantConnector
{
    use BaseBodyServiceTrait;
    use ParticipantConnectServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateConnect($data);
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
        // Insert user and get new user ID
        $result = $this->repository->connect((object)$data);
        return $this->createTokenData($result);
    }
}
