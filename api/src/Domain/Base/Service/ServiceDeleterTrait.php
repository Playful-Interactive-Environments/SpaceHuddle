<?php

namespace App\Domain\Base\Service;

/**
 * Description of the common delete service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceDeleterTrait
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
        $this->validator->validateExists($id);
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
        $id = $data["id"];
        $this->repository->deleteById($id);// Commit all changes

        $entityName = $this->repository->getEntityName();
        return (object)[
            "state" => "Success",
            "message" => "$entityName was successfully deleted."
        ];
    }
}
