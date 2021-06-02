<?php


namespace App\Domain\Session\Service;


use App\Domain\Session\Data\SessionData;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Session\Service\SessionValidator;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

class SessionCreate
{
    private SessionRepository $repository;

    private SessionValidator $validator;

    private LoggerInterface $logger;

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
        $this->repository = $repository;
        $this->validator = $validator;
        $this->logger = $loggerFactory
            ->addFileHandler("session_creator.log")
            ->createLogger();
    }

    /**
     * Create a new session.
     *
     * @param array<mixed> $data The form data
     *
     * @return SessionData The new session
     */
    public function createSession(string $userId, array $data): SessionData
    {
        // Input validation
        $this->validator->validateSessionCreate($data);

        // Map form data to session DTO (model)
        $session = (object)$data;

        // Insert session and get new session ID
        $result = $this->repository->inserSession($userId, $session);

        // Logging
        $this->logger->info("Session created successfully: $result->id");

        return $result;
    }
}
