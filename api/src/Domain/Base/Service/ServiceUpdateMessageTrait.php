<?php

namespace App\Domain\Base\Service;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceUpdateMessageTrait
{
    use ServiceUpdaterTrait;

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
        $result = $this->repository->update((object)$data);

        $entityName = $this->repository->getEntityName();
        return (object)[
            "state" => "Success",
            "message" => "$entityName updated successfully: $result->id"
        ];
    }
}
