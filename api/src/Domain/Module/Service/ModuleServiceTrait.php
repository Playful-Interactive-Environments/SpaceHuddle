<?php

namespace App\Domain\Module\Service;

use App\Database\TransactionInterface;
use App\Domain\Module\Repository\ModuleRepository;
use App\Factory\LoggerFactory;

trait ModuleServiceTrait
{
    protected ModuleRepository $repository;
    protected ModuleValidator $validator;

    /**
     * The constructor.
     *
     * @param ModuleRepository $repository The repository
     * @param ModuleValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        ModuleRepository $repository,
        ModuleValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
