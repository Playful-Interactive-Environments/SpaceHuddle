<?php

namespace App\Domain\Base\Service;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceUpdaterTrait
{
    use BaseManipulationServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $id = $data["id"];

        // Input validation
        $this->validator->validateUpdate($id, $data);
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
        return $this->repository->update((object)$data);
    }
}
