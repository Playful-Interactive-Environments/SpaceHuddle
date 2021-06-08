<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\ServiceUpdater;
use App\Domain\User\Data\UserData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserUpdater extends ServiceUpdater
{
    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
    }

    /**
     * change the password for the logged in user.
     *
     * @param string $userId The user id
     * @param array<string, mixed> $data The request data
     *
     * @return mixed
     */
    public function servicePassword(string $userId, array $data): mixed
    {
        // Input validation
        $this->validator->validatePasswordUpdate($userId, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $userId;

        // Update the user
        $result = $this->repository->updatePassword($user);

        // Logging
        $this->logger->info("The password was successfully updated: $userId");

        return [
                "state" => "Success",
                "message" => "The password was successfully updated."
            ];
    }
}
