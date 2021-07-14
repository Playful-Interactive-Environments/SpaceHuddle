<?php

namespace App\Domain\Idea\Service;

use App\Database\TransactionInterface;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Factory\LoggerFactory;

trait IdeaServiceTrait
{
    protected IdeaRepository $repository;
    protected IdeaValidator $validator;

    /**
     * The constructor.
     *
     * @param IdeaRepository $repository The repository
     * @param IdeaValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        IdeaRepository $repository,
        IdeaValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
