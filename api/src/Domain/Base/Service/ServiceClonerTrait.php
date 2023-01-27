<?php

namespace App\Domain\Base\Service;

use App\Domain\Base\Repository\GenericException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

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
        $this->validator->validateExists($data["id"]);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return object|null The newly created entity.
     * @throws GenericException|Exception
     */
    protected function serviceExecution(
        array $data
    ): object|null {
        // Clone an entity by its ID and return the new ID
        return $this->repository->clone($data["id"], $data["userId"], null);
    }
}
