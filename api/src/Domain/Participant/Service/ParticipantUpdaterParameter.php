<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseManipulationServiceTrait;

/**
 * Update participant service.
 */
class ParticipantUpdaterParameter
{
    use BaseManipulationServiceTrait;
    use ParticipantServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
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
        $this->repository->updateParameter((object)$data);

        return (object)[
            "state" => "Success",
            "message" => "The parameters has successfully updated."
        ];
    }
}
