<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Data\AuthorisationType;
use App\Domain\Base\Service\ServiceDeleterTrait;
use App\Domain\User\Repository\UserRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\LoggerFactory;

/**
 * Service.
 */
final class UserDeleter
{
    use ServiceDeleterTrait;
    use UserServiceTrait;

    /**
     * Define authorised roles for the service.
     */
    protected function setPermission(): void
    {
        $this->authorisationPermissionList = [
            AuthorisationType::USER
        ];
        $this->entityPermissionList = [
            SessionRoleType::MODERATOR,
            SessionRoleType::FACILITATOR
        ];
    }
}
