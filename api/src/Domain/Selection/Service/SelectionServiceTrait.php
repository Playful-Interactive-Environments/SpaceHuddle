<?php

namespace App\Domain\Selection\Service;

use App\Database\TransactionInterface;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Factory\LoggerFactory;

trait SelectionServiceTrait
{
    protected SelectionRepository $repository;
    protected SelectionValidator $validator;

    /**
     * The constructor.
     *
     * @param SelectionRepository $repository The repository
     * @param SelectionValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SelectionRepository $repository,
        SelectionValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
