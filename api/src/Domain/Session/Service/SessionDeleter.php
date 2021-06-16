<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationType;
use App\Domain\Base\Service\ServiceDeleterTrait;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Service.
 */
class SessionDeleter
{
    use ServiceDeleterTrait;
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
}
