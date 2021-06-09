<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Domain\Base\Service\ServiceCreator;
use App\Domain\User\Data\UserData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserCreator extends ServiceCreator
{
    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
    }
}
