<?php

namespace App\Action\TaskParticipantState;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantState\Service\TaskParticipantStateFromSessionReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the participant states for the specified session.
 *
 * @OA\Get(
 *   path="/session/{id}/participant_state/",
 *   summary="List of all participant states for the session.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TaskParticipantStateData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantStateReadAllFromSessionAction
{
    use ActionTrait;
    protected TaskParticipantStateFromSessionReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantStateFromSessionReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantStateFromSessionReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
