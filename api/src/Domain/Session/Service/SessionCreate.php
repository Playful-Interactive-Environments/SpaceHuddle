<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\AbstractData;
use App\Domain\Base\Service\ServiceCreator;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\LoggerFactory;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCreate extends ServiceCreator
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
    }

    /**
     * Functionality of the session create service.
     *
     * @param array<string, mixed> $data The form data
     * @param ?string $userId Id of the logged in user
     *
     * @return AbstractData|null Result entity
     */
    public function service(array $data, ?string $userId = null): AbstractData|null
    {
        // Input validation
        $this->validator->validateCreate($data);

        // Map form data to session DTO (model)
        $session = (object)$data;

        // Insert session and get new session ID
        $result = $this->repository->insert($session, $userId);

        // Logging
        $this->logger->info("Session created successfully: $result->id");

        return $result;
    }
}
