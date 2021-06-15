<?php

namespace App\Domain\Session\Service;

use App\Database\TransactionInterface;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\LoggerFactory;

trait SessionServiceTrait
{
    protected SessionRepository $repository;
    protected SessionValidator $validator;

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
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
