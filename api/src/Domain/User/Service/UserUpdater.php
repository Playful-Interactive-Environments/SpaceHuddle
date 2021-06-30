<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseManipulationServiceTrait;

/**
 * Service.
 */
final class UserUpdater
{
    use BaseManipulationServiceTrait;
    use UserServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validatePasswordUpdate($data);
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
        $this->repository->updatePassword((object)$data);

        return (object)[
            "state" => "Success",
            "message" => "The password was successfully updated."
        ];
    }
}
