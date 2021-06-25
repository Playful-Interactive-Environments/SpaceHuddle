<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceUpdateMessageTrait
{
    use ServiceUpdaterTrait {
        ServiceUpdaterTrait::service as private updateService;
    }

    /**
     * Functionality of the update service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $result = $this->updateService($authorisation, $bodyData, $urlData);

        // Logging
        $entityName = $this->repository->getEntityName();
        return [
            "state" => "Success",
            "message" => "$entityName updated successfully: $result->id"
        ];
    }
}
