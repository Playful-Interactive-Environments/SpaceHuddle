<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Service\ServiceSingleReader;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\LoggerFactory;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader extends ServiceSingleReader
{
    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param SessionValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRepository $repository,
        SessionValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->authorisationPermissionList = [AuthorisationRoleType::USER, AuthorisationRoleType::PARTICIPANT];
    }
    /**
     * Functionality of the read single entity service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|AbstractData|null {
        parent::service($authorisation, $bodyData, $urlData);
        $id = $urlData["id"];
        $result = $this->repository->getByIdAuthorised($id, $authorisation);
        return $result;
    }
}
