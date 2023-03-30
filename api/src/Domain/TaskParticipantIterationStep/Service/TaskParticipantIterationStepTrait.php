<?php

namespace App\Domain\TaskParticipantIterationStep\Service;

use App\Database\TransactionInterface;
use App\Domain\TaskParticipantIterationStep\Repository\TaskParticipantIterationStepRepository;
use App\Factory\LoggerFactory;

trait TaskParticipantIterationStepTrait
{
    protected TaskParticipantIterationStepRepository $repository;
    protected TaskParticipantIterationStepValidator $validator;

    /**
     * The constructor.
     *
     * @param TaskParticipantIterationStepRepository $repository The repository
     * @param TaskParticipantIterationStepValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TaskParticipantIterationStepRepository $repository,
        TaskParticipantIterationStepValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
