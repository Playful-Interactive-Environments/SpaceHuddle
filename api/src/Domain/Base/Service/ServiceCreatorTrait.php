<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;

/**
 * Description of the common insert service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceCreatorTrait
{
    use BaseServiceTrait;

    /**
     * Functionality of the create service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     * @throws AuthorisationException|GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->checkPermission($authorisation, $urlData);
        $data = array_merge($bodyData, $urlData);

        // Input validation
        $this->validator->validateCreate($data);

        $this->transaction->begin();
        // Insert entity and get new ID
        $result = $this->repository->insert((object)$data);
        $this->transaction->commit();

        // Logging
        $entityName = $this->repository->getEntityName();
        $this->logger->info("$entityName created successfully: $result->id");

        return $result;
    }
}
