<?php

namespace App\Domain\Participant\Service;

use App\Database\TransactionInterface;
use App\Domain\Base\Data\TokenData;
use App\Domain\Participant\Data\ParticipantData;
use App\Domain\Participant\Data\ParticipantTokenData;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

trait ParticipantConnectServiceTrait
{
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
     * Create a participant token
     * @param ParticipantData|null $participant Participant
     * @return ParticipantTokenData|null Result token
     */
    protected function createTokenData(?ParticipantData $participant): ?ParticipantTokenData
    {
        if (is_null($participant)) {
            return null;
        }

        $jwt = $this->jwtAuth->createJwt(
            [
                "action" => "login",
                "participantId" => $participant->id,
                "browserKey" => $participant->browserKey
            ]
        );

        $tokenResult = new TokenData([
            "message" => "Successful connected.",
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ]);

        return new ParticipantTokenData($participant, $tokenResult);
    }
}
