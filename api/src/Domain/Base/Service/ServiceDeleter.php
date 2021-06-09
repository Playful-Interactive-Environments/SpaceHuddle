<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Data\AuthorisationData;

/**
 * Description of the common delete service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceDeleter extends AbstractService
{
    /**
     * Functionality of the delete service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws \App\Domain\Base\Data\AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        $id = $data["id"];

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
