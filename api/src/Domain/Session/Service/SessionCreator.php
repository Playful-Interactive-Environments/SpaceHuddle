<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Service\AbstractService;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\LoggerFactory;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCreator extends AbstractService
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
        $this->authorisationPermissionList = [AuthorisationRoleType::USER];
        $this->entityPermissionList = [
            UserRoleType::MODERATOR
        ];
    }

    /**
     * Functionality of the session create service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        // Input validation
        $this->validator->validateCreate($data);

        // Map form data to session DTO (model)
        $session = (object)$data;
        $session->userId = $authorisation->id;

        $this->transaction->begin();
        // Insert session and get new session ID
        $result = $this->repository->insert($session);
        $this->transaction->commit();

        // Logging
        $this->logger->info("Session created successfully: $result->id");

        return $result;
    }
}
