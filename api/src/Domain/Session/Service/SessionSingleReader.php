<?php

namespace App\Domain\Session\Service;

use App\Database\TransactionInterface;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Service\ServiceSingleReader;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\LoggerFactory;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader extends ServiceSingleReader
{
    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param SessionValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRepository $repository,
        SessionValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->authorisationPermissionList = [AuthorisationRoleType::USER, AuthorisationRoleType::PARTICIPANT];
    }
}
