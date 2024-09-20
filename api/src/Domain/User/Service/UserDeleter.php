<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Service.
 */
final class UserDeleter
{
    use ServiceDeleterTrait;
    use UserServiceTrait;

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
        $this->repository->deleteById($id, false);// Commit all changes

        $entityName = $this->repository->getEntityName();
        return (object)[
            "state" => "Success",
            "message" => "$entityName was successfully deleted."
        ];
    }
}
