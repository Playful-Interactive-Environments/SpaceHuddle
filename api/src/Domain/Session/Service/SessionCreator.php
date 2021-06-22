<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCreator
{
    use BaseServiceTrait;
    use SessionServiceTrait;

    /**
     * Functionality of the session create service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     * @throws GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $data = array_merge($bodyData, $urlData);

        // Input validation
        $this->validator->validateCreate($data);

        // Map form data to session DTO (model)
        $session = (object)$data;
        $session->userId = $authorisation->id;

        $this->transaction->begin();
        // Insert session and get new session ID
        $result = $this->repository->insert($session);
        $this->transaction->commit();

        // Logging
        $this->logger->info("Session created successfully: $result->id");

        return $result;
    }
}
