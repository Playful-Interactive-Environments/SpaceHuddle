<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Data\AuthorisationType;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceUpdaterTrait;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Service.
 */
class SessionUpdater
{
    use ServiceUpdaterTrait {
        ServiceUpdaterTrait::service as private genericService;
    }
    use SessionServiceTrait;

    /**
     * Define authorised roles for the service.
     */
    protected function setPermission(): void
    {
        $this->authorisationPermissionList = [
            AuthorisationType::USER
        ];
        $this->entityPermissionList = [
            SessionRoleType::MODERATOR
        ];
    }

    /**
     * Functionality of the update service.
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
        $result = $this->genericService($authorisation, $bodyData, $urlData);
        $result->role = strtoupper(SessionRoleType::MODERATOR);
        return $result;
    }
}
