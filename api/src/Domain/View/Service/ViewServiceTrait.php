<?php

namespace App\Domain\View\Service;

use App\Database\TransactionInterface;
use App\Domain\View\Repository\ViewRepository;
use App\Factory\LoggerFactory;

trait ViewServiceTrait
{
    protected ViewRepository $repository;
    protected ViewValidator $validator;

    /**
     * The constructor.
     *
     * @param ViewRepository $repository The repository
     * @param ViewValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        ViewRepository $repository,
        ViewValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
