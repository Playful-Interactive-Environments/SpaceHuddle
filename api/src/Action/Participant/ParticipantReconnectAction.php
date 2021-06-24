<?php

namespace App\Action\Participant;

use App\Action\Base\ActionTrait;
use App\Domain\Participant\Service\ParticipantReconnector;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reconnecting to a session as a participant.
 *
 * @OA\Get(
 *   path="/api/participant/reconnect/{browserKey}/",
 *   summary="Reconnect to a session",
 *   tags={"Participant"},
 *   @OA\Parameter(in="path", name="browserKey", description="the generated browser_key from the last connection to
 *   the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ParticipantData")),
 *   @OA\Response(response="404", description="Not Found")
 * )
 */
class ParticipantReconnectAction
{
    use ActionTrait;
    protected ParticipantReconnector $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantReconnector $service The service
     */
    public function __construct(Responder $responder, ParticipantReconnector $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
