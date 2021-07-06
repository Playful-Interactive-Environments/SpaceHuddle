<?php

namespace App\Domain\Category\Service;

use App\Database\TransactionInterface;
use App\Domain\Category\Repository\CategoryRepository;
use App\Factory\LoggerFactory;

trait CategoryServiceTrait
{
    protected CategoryRepository $repository;
    protected CategoryValidator $validator;

    /**
     * The constructor.
     *
     * @param CategoryRepository $repository The repository
     * @param CategoryValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        CategoryRepository $repository,
        CategoryValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
