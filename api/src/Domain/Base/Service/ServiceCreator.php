<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;

/**
 * Description of the common insert service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceCreator extends AbstractService
{
    /**
     * Functionality of the create service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|AbstractData|null {
        parent::service($authorisation, $bodyData, $urlData);
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
