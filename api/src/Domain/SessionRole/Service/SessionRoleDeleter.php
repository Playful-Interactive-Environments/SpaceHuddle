<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceDeleterTrait;
use App\Domain\Idea\Type\IdeaState;

/**
 * Delete session role service.
 */
class SessionRoleDeleter
{
    use ServiceDeleterTrait;
    use SessionRoleServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateDelete($data);
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
        $this->repository->delete((object)$data);

        return (object)[
            "state" => "Success",
            "message" => "Session role was successfully deleted."
        ];
    }
}
