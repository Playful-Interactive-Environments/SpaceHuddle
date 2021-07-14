<?php

namespace App\Domain\Resource\Service;

use App\Database\TransactionInterface;
use App\Domain\Resource\Repository\ResourceRepository;
use App\Factory\LoggerFactory;

trait ResourceServiceTrait
{
    protected ResourceRepository $repository;
    protected ResourceValidator $validator;

    /**
     * The constructor.
     *
     * @param ResourceRepository $repository The repository
     * @param ResourceValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        ResourceRepository $repository,
        ResourceValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
