<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Data\AuthorisationRole;
use App\Domain\Base\Service\ServiceDeleter;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;

/**
 * Service.
 */
final class UserDeleter extends ServiceDeleter
{
    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
        $this->permission = [AuthorisationRole::USER];
    }
}
