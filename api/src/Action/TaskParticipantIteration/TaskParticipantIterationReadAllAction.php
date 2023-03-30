<?php

namespace App\Action\TaskParticipantIteration;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIteration\Service\TaskParticipantIterationReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the participant iterations for the specified task.
 *
 * @OA\Get(
 *   path="/task/{id}/participant_iteration/",
 *   summary="List of all participant iterations for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TaskParticipantIterationData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationReadAllAction
{
    use ActionTrait;
    protected TaskParticipantIterationReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
