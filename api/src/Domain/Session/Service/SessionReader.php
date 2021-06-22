<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseServiceTrait;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionReader
{
    use BaseServiceTrait;
    use SessionServiceTrait;

    /**
     * Functionality of the read all service.
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
        return $this->repository->getAllAuthorised($authorisation->id, $authorisation);
    }
}
