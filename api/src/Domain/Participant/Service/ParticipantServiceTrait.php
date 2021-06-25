<?php

namespace App\Domain\Participant\Service;

use App\Database\TransactionInterface;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Factory\LoggerFactory;

trait ParticipantServiceTrait
{
    protected ParticipantRepository $repository;
    protected ParticipantValidator $validator;

    /**
     * The constructor.
     *
     * @param ParticipantRepository $repository The repository
     * @param ParticipantValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        ParticipantRepository $repository,
        ParticipantValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
