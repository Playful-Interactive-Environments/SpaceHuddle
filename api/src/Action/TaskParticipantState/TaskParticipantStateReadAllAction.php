<?php

namespace App\Action\TaskParticipantState;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantState\Service\TaskParticipantStateReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the participant states for the specified task.
 *
 * @OA\Get(
 *   path="/task/{id}/participant_state/",
 *   summary="List of all participant states for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
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
class TaskParticipantStateReadAllAction
{
    use ActionTrait;
    protected TaskParticipantStateReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantStateReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantStateReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
