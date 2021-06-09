<?php

namespace App\Domain\Base\Service;

use App\Database\TransactionInterface;
use App\Domain\Base\Repository\AbstractRepository;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Factory\LoggerFactory;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 * @package App\Domain\Base\Service
 */
abstract class AbstractService
{
    protected AbstractRepository $repository;
    protected AbstractValidator $validator;
    protected TransactionInterface $transaction;
    protected LoggerInterface $logger;
    protected array $permission;

    /**
     * The constructor.
     *
     * @param AbstractRepository $repository The repository
     * @param AbstractValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        AbstractRepository $repository,
        AbstractValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->transaction = $transaction;
        $this->logger = $loggerFactory
            ->addFileHandler("service.log")
            ->createLogger();
        $this->permission = [];
    }

    /**
     * Is authorisation required for the service and if so, which one?
     * @param AuthorisationData $authorisation Authorisation data
     * @return bool TRUE if the rights for executing the service are available.
     */
    public function hasPermission(AuthorisationData $authorisation): bool
    {
        return (
            sizeof($this->permission) === 0 or
            in_array($authorisation->role, $this->permission)
        );
    }

    /**
     * Functionality of the service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        if (!$this->hasPermission($authorisation)) {
            http_response_code(StatusCodeInterface::STATUS_UNAUTHORIZED);
            throw new AuthorisationException("User has no rights for this service");
        }
        return null;
    }
}
