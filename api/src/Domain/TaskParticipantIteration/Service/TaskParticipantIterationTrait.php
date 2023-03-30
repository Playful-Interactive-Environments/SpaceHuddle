<?php

namespace App\Domain\TaskParticipantIteration\Service;

use App\Database\TransactionInterface;
use App\Domain\TaskParticipantIteration\Repository\TaskParticipantIterationRepository;
use App\Factory\LoggerFactory;

trait TaskParticipantIterationTrait
{
    protected TaskParticipantIterationRepository $repository;
    protected TaskParticipantIterationValidator $validator;

    /**
     * The constructor.
     *
     * @param TaskParticipantIterationRepository $repository The repository
     * @param TaskParticipantIterationValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TaskParticipantIterationRepository $repository,
        TaskParticipantIterationValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
