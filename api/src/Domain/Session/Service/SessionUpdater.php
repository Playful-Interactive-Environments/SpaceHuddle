<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceUpdaterTrait;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Update session service.
 */
class SessionUpdater
{
    use ServiceUpdaterTrait {
        ServiceUpdaterTrait::service as private genericService;
    }
    use SessionServiceTrait;

    /**
     * Functionality of the update service.
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
        $result = $this->genericService($authorisation, $bodyData, $urlData);
        $result->role = strtoupper(SessionRoleType::MODERATOR);
        return $result;
    }
}
