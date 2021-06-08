<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\AbstractData;

/**
 * Description of the common delete service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceDeleter extends AbstractService
{
    /**
     * Functionality of the delete service.
     *
     * @param string $id The entity id
     *
     * @return array<string, string>
     */
    public function service(string $id): array
    {
        // Input validation
        $this->validator->validateExists($id);

        $this->repository->deleteById($id);

        $entityName = $this->repository->getEntityName();
        return [
            "state" => "Success",
            "message" => "$entityName was successfully deleted."
        ];
    }
}
