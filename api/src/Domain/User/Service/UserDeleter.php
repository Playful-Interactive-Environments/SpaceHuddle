<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Service\ServiceDeleter;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Type\UserRoleType;
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
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->authorisationPermissionList = [AuthorisationRoleType::USER];
        $this->entityPermissionList = [
            UserRoleType::MODERATOR,
            UserRoleType::FACILITATOR
        ];
    }
}
