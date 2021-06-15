<?php

namespace App\Domain\User\Service;

use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseServiceTrait;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Service.
 */
final class UserLogin
{
    use BaseServiceTrait {
        BaseServiceTrait::service as private genericService;
    }

    protected UserRepository $repository;
    protected UserValidator $validator;
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
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Functionality of the login service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     * @throws AuthorisationException|GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->genericService($authorisation, $bodyData, $urlData);
        $data = array_merge($bodyData, $urlData);

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
