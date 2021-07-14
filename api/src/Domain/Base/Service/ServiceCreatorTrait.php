<?php

namespace App\Domain\Base\Service;

/**
 * Description of the common insert service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceCreatorTrait
{
    use BaseManipulationServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateCreate($data);
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
        // Insert entity and get new ID
        return $this->repository->insert((object)$data);
    }
}
