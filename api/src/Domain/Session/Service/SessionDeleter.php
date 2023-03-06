<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete session service.
 */
class SessionDeleter
{
    use ServiceDeleterTrait;
    use SessionServiceTrait;

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
        $parentId = $this->repository->deleteById($id, false);// Commit all changes

        $entityName = $this->repository->getEntityName();
        return (object)[
            "state" => "Success",
            "message" => "$entityName was successfully deleted.",
            "parentId" => $parentId
        ];
    }
}
