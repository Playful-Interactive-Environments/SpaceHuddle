<?php

namespace App\Domain\Vote\Service;

use App\Database\TransactionInterface;
use App\Domain\Vote\Repository\VoteRepository;
use App\Factory\LoggerFactory;

trait VoteServiceTrait
{
    protected VoteRepository $repository;
    protected VoteValidator $validator;

    /**
     * The constructor.
     *
     * @param VoteRepository $repository The repository
     * @param VoteValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        VoteRepository $repository,
        VoteValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
