<?php

namespace App\Domain\User\Service;

use App\Domain\User\Data\UserData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserUpdater
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
            ->addFileHandler("user_updater.log")
            ->createLogger();
    }

    /**
     * Update user.
     *
     * @param string $userId The user id
     * @param array<mixed> $data The request data
     *
     * @return void
     */
    public function updateUser(string $userId, array $data): UserData
    {
        // Input validation
        $this->userValidator->validateUserUpdate($userId, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $userId;

        // Update the user
        $userResult = $this->repository->updateUser($user);

        // Logging
        $this->logger->info("User updated successfully: $userId");

        return $userResult;
    }

    /**
     * Update user.
     *
     * @param string $userId The user id
     * @param array<mixed> $data The request data
     *
     * @return void
     */
    public function updatePassword(string $userId, array $data): mixed
    {
        // Input validation
        $this->userValidator->validatePasswordUpdate($userId, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $userId;

        // Update the user
        $userResult = $this->repository->updatePassword($user);

        // Logging
        $this->logger->info("The password was successfully updated: $userId");

        return [
                "state" => "Success",
                "message" => "The password was successfully updated."
            ];
    }
}
