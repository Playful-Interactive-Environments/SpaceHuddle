<?php

namespace App\Domain\Participant\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\TokenData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseServiceTrait;
use App\Domain\Participant\Data\ParticipantTokenData;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Service.
 */
class ParticipantConnector
{
    use BaseServiceTrait;

    protected ParticipantRepository $repository;
    protected ParticipantValidator $validator;

    protected JwtAuth $jwtAuth;

    /**
     * The constructor.
     *
     * @param ParticipantRepository $repository The repository
     * @param ParticipantValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     * @param JwtAuth $jwtAuth The jwt authorization
     */
    public function __construct(
        ParticipantRepository $repository,
        ParticipantValidator $validator,
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
        $this->checkPermission($authorisation, $urlData);
        $data = array_merge($bodyData, $urlData);

        // Input validation
        $this->validator->validateConnect($data);

        // Insert user and get new user ID
        $result = $this->repository->connect((object)$data);

        $jwt = $this->jwtAuth->createJwt(
            [
                "participantId" => $result->id,
                "browserKey" => $result->browserKey
            ]
        );

        $tokenResult = new TokenData([
            "message" => "Successful connected.",
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ]);

        return new ParticipantTokenData($result, $tokenResult);
    }
}
