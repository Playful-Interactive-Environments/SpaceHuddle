<?php

namespace App\Domain\Tutorial\Service;

use App\Database\TransactionInterface;
use App\Domain\Tutorial\Repository\TutorialRepository;
use App\Domain\View\Repository\ViewRepository;
use App\Factory\LoggerFactory;

trait TutorialServiceTrait
{
    protected TutorialRepository $repository;
    protected TutorialValidator $validator;

    /**
     * The constructor.
     *
     * @param TutorialRepository $repository The repository
     * @param TutorialValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        TutorialRepository $repository,
        TutorialValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
    }
}
