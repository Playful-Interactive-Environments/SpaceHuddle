<?php

namespace App\Domain\Participant\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Service\AbstractService;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Service.
 */
class ParticipantConnector extends AbstractService
{
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
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Functionality of the login service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|AbstractData|null {
        parent::service($authorisation, $bodyData, $urlData);
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

        return [
            "message" => "Successful connected.",
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ];
    }
}
