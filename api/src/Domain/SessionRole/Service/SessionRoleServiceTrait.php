<?php

namespace App\Domain\SessionRole\Service;

use App\Database\TransactionInterface;
use App\Domain\SessionRole\Repository\SessionRoleRepository;
use App\Factory\LoggerFactory;

trait SessionRoleServiceTrait
{
    protected SessionRoleRepository $repository;
    protected SessionRoleValidator $validator;

    /**
     * The constructor.
     *
     * @param SessionRoleRepository $repository The repository
     * @param SessionRoleValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRoleRepository $repository,
        SessionRoleValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
