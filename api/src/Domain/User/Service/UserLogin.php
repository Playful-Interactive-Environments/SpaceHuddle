<?php

namespace App\Domain\User\Service;

use App\Domain\User\Data\UserData;
use App\Domain\Service\Authorization;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserLogin
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
     * Perform a login with an existing user.
     *
     * @param array<mixed> $data The form data
     *
     * @return array<mixed> The access token
     */
    public function loginUser(array $data): mixed
    {
        // Input validation
        $this->userValidator->validateLogin($data);

        // Insert user and get new user ID
        $userResult = $this->repository->getUserByName($data["username"]);
        $jwt = Authorization::generateToken(
            [
                "userId" => $userResult->id,
                "username" => $userResult->username
            ]
        );

        return [
            "message" => "Successful login.",
            "accessToken" => $jwt
        ];
    }
}
