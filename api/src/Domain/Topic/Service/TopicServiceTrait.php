<?php

namespace App\Domain\Topic\Service;

use App\Database\TransactionInterface;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\LoggerFactory;

trait TopicServiceTrait
{
    protected TopicRepository $repository;
    protected TopicValidator $validator;

    /**
     * The constructor.
     *
     * @param TopicRepository $repository The repository
     * @param TopicValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TopicRepository $repository,
        TopicValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
