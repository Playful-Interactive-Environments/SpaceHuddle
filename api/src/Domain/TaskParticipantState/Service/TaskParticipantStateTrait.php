<?php

namespace App\Domain\TaskParticipantState\Service;

use App\Database\TransactionInterface;
use App\Domain\TaskParticipantState\Repository\TaskParticipantStateRepository;
use App\Factory\LoggerFactory;

trait TaskParticipantStateTrait
{
    protected TaskParticipantStateRepository $repository;
    protected TaskParticipantStateValidator $validator;

    /**
     * The constructor.
     *
     * @param TaskParticipantStateRepository $repository The repository
     * @param TaskParticipantStateValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TaskParticipantStateRepository $repository,
        TaskParticipantStateValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
