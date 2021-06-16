<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Data\AuthorisationType;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Session\Type\SessionRoleType;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Description of the common permission service functionality.
 * @package App\Domain\Base\Service
 */
trait ServicePermissionTrait
{
    protected array $authorisationPermissionList;
    protected array $entityPermissionList;

    /**
     * Define authorised roles for the service.
     */
    protected function setPermission(): void
    {
        $this->authorisationPermissionList = [
            AuthorisationType::PARTICIPANT,
            AuthorisationType::USER,
            AuthorisationType::NONE
        ];
        $this->entityPermissionList = [
            SessionRoleType::MODERATOR,
            SessionRoleType::FACILITATOR,
            SessionRoleType::PARTICIPANT
        ];
    }

    /**
     * Check if the user role is part of the authorised roles.
     * @param string|null $role User role
     * @param array $authorizedRoles List of authorised roles.
     * @return bool Authorisation state
     */
    protected static function isAuthorized(?string $role, array $authorizedRoles = []): bool
    {
        if (isset($role)) {
            $role = strtoupper($role);
            $authorizedRoles = array_map("strtoupper", $authorizedRoles);
            return (
                sizeof($authorizedRoles) === 0 or
                in_array($role, $authorizedRoles)
            );
        }
        return false;
    }

    /**
     * Is authorisation required for the service and if so, which one?
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $urlData Url parameter from the request
     * @return bool TRUE if the rights for executing the service are available.
     * @throws GenericException
     */
    protected function hasPermission(AuthorisationData $authorisation, array $urlData): bool
    {
        $permission = self::isAuthorized($authorisation->type, $this->authorisationPermissionList);

        if ($permission) {
            if (key_exists("id", $urlData)) {
                $id = $urlData["id"];
                $specificEntityRole = $this->repository->getAuthorisationRole($authorisation, $id);
                $permission = self::isAuthorized($specificEntityRole, $this->entityPermissionList);
            } elseif (sizeof($urlData) > 0) {
                $urlParameterName = array_key_first($urlData);
                if (str_ends_with($urlParameterName, "Id")) {
                    $id = $urlData[$urlParameterName];
                    $repository = $this->repository->getParentRepository();
                    if (isset($repository)) {
                        $specificEntityRole = $repository->getAuthorisationRole($authorisation, $id);
                        $permission = self::isAuthorized($specificEntityRole, $this->entityPermissionList);
                    }
                }
            }
        }

        return $permission;
    }

    /**
     * Service functionality to check permission.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     * @throws AuthorisationException|GenericException
     */
    public function checkPermission(
        AuthorisationData $authorisation,
        array $urlData
    ): void {
        if (!$this->hasPermission($authorisation, $urlData)) {
            http_response_code(StatusCodeInterface::STATUS_UNAUTHORIZED);
            throw new AuthorisationException("User has no rights for this service or entity");
        }
    }

}
