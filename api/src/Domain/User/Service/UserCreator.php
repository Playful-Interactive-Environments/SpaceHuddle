<?php

namespace App\Domain\User\Service;

use App\Domain\User\Data\UserData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserCreator
{
    private UserRepository $repository;

    private UserValidator $userValidator;

    private LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $userValidator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $userValidator,
        LoggerFactory $loggerFactory
    ) {
        $this->repository = $repository;
        $this->userValidator = $userValidator;
        $this->logger = $loggerFactory
            ->addFileHandler('user_creator.log')
            ->createLogger();
    }

    /**
     * Create a new user.
     *
     * @param array<mixed> $data The form data
     *
     * @return UserData The new user
     */
    public function createUser(array $data): UserData
    {
        // Input validation
        $this->userValidator->validateUserCreate($data);

        // Map form data to user DTO (model)
        $user = (object)$data;

        // Hash password
        if ($user->password) {
            $user->password = (string)password_hash($user->password, PASSWORD_DEFAULT);
        }

        // Insert user and get new user ID
        $userResult = $this->repository->insertUser($user);

        // Logging
        $this->logger->info(sprintf('User created successfully: %s', $userResult->username));

        return $userResult;
    }
}
