<?php

namespace App\Domain\Base\Service;

use App\Database\TransactionInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 */
trait BaseServiceTrait
{
    use ServicePermissionTrait;

    protected TransactionInterface $transaction;
    protected LoggerInterface $logger;

    /**
     * Basic setup for constructor.
     *
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     * @return void
     */
    protected function setUp(
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ): void {
        $this->transaction = $transaction;
        $this->logger = $loggerFactory
            ->addFileHandler("service.log")
            ->createLogger();
        $this->setPermission();
    }
}
