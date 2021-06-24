<?php

namespace App\Domain\Task\Service;

use App\Database\TransactionInterface;
use App\Domain\Task\Repository\TaskRepository;
use App\Factory\LoggerFactory;

trait TaskServiceTrait
{
    protected TaskRepository $repository;
    protected TaskValidator $validator;

    /**
     * The constructor.
     *
     * @param TaskRepository $repository The repository
     * @param TaskValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TaskRepository $repository,
        TaskValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
