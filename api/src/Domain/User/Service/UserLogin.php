<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\AbstractService;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Service.
 */
final class UserLogin extends AbstractService
{
    protected JwtAuth $jwtAuth;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     * @param JwtAuth $jwtAuth The jwt authorization
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        LoggerFactory $loggerFactory,
        JwtAuth $jwtAuth
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Perform a login with an existing user.
     * @param array<string, mixed> $data The form data
     * @return array<string, mixed> The access token
     */
    public function service(array $data): array
    {
        // Input validation
        $this->validator->validateLogin($data);

        // Insert user and get new user ID
        $userResult = $this->repository->getUserByName($data["username"]);

        $jwt = $this->jwtAuth->createJwt(
            [
                "userId" => $userResult->id,
                "username" => $userResult->username
            ]
        );

        return [
            "message" => "Successful login.",
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ];
    }
}
