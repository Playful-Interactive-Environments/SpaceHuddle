<?php

namespace App\Domain\Hierarchy\Service;

use App\Database\TransactionInterface;
use App\Domain\Hierarchy\Repository\HierarchyRepository;
use App\Factory\LoggerFactory;

trait HierarchyServiceTrait
{
    protected HierarchyRepository $repository;
    protected HierarchyValidator $validator;

    /**
     * The constructor.
     *
     * @param HierarchyRepository $repository The repository
     * @param HierarchyValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        HierarchyRepository $repository,
        HierarchyValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
