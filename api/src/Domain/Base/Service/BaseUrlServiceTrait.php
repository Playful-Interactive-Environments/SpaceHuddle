<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;
use App\Database\TransactionInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 */
trait BaseUrlServiceTrait
{
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
        /*$this->logger = $loggerFactory
            ->addFileHandler("service.log")
            ->createLogger();*/
    }

    /**
     * Functionality of the service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->repository->setAuthorisation($authorisation);

        // validation
        $this->serviceValidation($urlData);

        // Executes the repository instructions assigned to the service.
        return $this->serviceExecution($urlData);
    }

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
    }
}
