<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;

/**
 * Service.
 */
final class UserConfirmOther
{
    use BaseBodyServiceTrait;
    use UserServiceJwtTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateAdmin($data);
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
        $id = $data["id"];
        $this->repository->confirmUser($id);

        return (object)[
            "state" => "Success",
            "message" => "The email was successfully confirmed."
        ];
    }
}
