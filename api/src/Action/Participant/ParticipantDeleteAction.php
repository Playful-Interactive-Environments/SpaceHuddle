<?php

namespace App\Action\Participant;

use App\Action\Base\ActionTrait;
use App\Domain\Participant\Service\ParticipantDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting the logged-in participant.
 *
 * @OA\Delete(
 *   path="/api/participant/",
 *   summary="Delete logged-in participant.",
 *   tags={"Participant"},
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantDeleteAction
{
    use ActionTrait;
    protected ParticipantDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantDeleter $service The service
     */
    public function __construct(Responder $responder, ParticipantDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
