<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseManipulationServiceTrait;

/**
 * Service.
 */
final class UserUpdaterParameter
{
    use BaseManipulationServiceTrait;
    use UserServiceTrait;

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
