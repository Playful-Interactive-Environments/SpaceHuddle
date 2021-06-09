<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Data\AuthorisationData;
use App\Domain\Base\Data\AuthorisationRole;
use App\Domain\Base\Service\AbstractService;
use App\Domain\Base\Service\ServiceCreator;
use App\Domain\Session\Repository\SessionRepository;
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
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRepository $repository,
        SessionValidator $validator,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
        $this->permission = [AuthorisationRole::USER];
    }

    /**
     * Functionality of the session create service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws \App\Domain\Base\Data\AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        // Input validation
        $this->validator->validateCreate($data);

        // Map form data to session DTO (model)
        $session = (object)$data;
        $session->userId = $authorisation->id;

        // Insert session and get new session ID
        $result = $this->repository->insert($session);

        // Logging
        $this->logger->info("Session created successfully: $result->id");

        return $result;
    }
}
