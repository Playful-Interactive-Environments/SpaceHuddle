<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;

trait UserServiceTrait
{
    protected UserRepository $repository;
    protected UserValidator $validator;

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
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
