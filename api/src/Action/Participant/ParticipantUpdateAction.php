<?php

namespace App\Action\Participant;

use App\Domain\Participant\Service\ParticipantUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action that sets participant status.
 *
 * @OA\Put(
 *   path="/participant/state/{state}/",
 *   summary="Set the participant state.",
 *   tags={"Participant"},
 *   @OA\Parameter(in="path", name="state",
 *     description="display status of the participant",
 *     required=true,
 *     @OA\Schema(ref="#/components/schemas/ParticipantState")),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantUpdateAction
{
    use ParticipantSelfActionTrait;
    protected ParticipantUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantUpdater $service The service
     */
    public function __construct(Responder $responder, ParticipantUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
