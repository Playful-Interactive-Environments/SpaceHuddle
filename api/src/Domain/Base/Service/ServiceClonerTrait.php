<?php

namespace App\Domain\Base\Service;

use App\Domain\Base\Repository\GenericException;

/**
 * Description of the common insert service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceClonerTrait
{
    use BaseManipulationServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateExists($data["sessionId"]);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return string|null Repository answer.
     * @throws GenericException
     */
    protected function serviceExecution(
        array $data
    ): object|null {
        // Clone an entity by its ID and return the new ID
        return $this->repository->clone($data["sessionId"], $data["userId"]);
    }
}
