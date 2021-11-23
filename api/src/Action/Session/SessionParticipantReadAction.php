<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionParticipantReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to read a list of participants associated with the session ID.
 *
 * @OA\Get(
 *   path="/session/{sessionId}/participants",
 *   summary="Participants for the session with the specified id.",
 *   tags={"Session"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of session to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ParticipantInfoData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionParticipantReadAction
{
    use ActionTrait;
    protected SessionParticipantReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionParticipantReader $service The service
     */
    public function __construct(Responder $responder, SessionParticipantReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
