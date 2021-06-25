<?php

namespace App\Domain\Participant\Service;

use App\Data\AuthorisationData;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\TokenData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseServiceTrait;
use App\Domain\Participant\Data\ParticipantTokenData;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Participant connect service.
 */
class ParticipantConnector
{
    use BaseServiceTrait;
    use ParticipantConnectServiceTrait;

    /**
     * Functionality of the login service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return ParticipantTokenData|null Service output
     * @throws GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): ParticipantTokenData|null {
        $data = array_merge($bodyData, $urlData);

        // Input validation
        $this->validator->validateConnect($data);

        // Insert user and get new user ID
        $result = $this->repository->connect((object)$data);
        return $this->createTokenData($result);
    }
}
