<?php

namespace App\Domain\User\Service;

use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
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
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     * @param JwtAuth $jwtAuth The jwt authorization
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory,
        JwtAuth $jwtAuth
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Functionality of the login service.
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
        $this->validator->validateLogin($data);

        // Insert user and get new user ID
        $result = $this->repository->getUserByName($data["username"]);

        $jwt = $this->jwtAuth->createJwt(
            [
                "userId" => $result->id,
                "username" => $result->username
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
