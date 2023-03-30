<?php

namespace App\Action\TaskParticipantIteration;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIteration\Service\TaskParticipantIterationLastReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the last participant iterations for the specified task.
 *
 * @OA\Get(
 *   path="/task/{id}/participant_iteration/last/",
 *   summary="Read the last participant iterations for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of task to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationReadLastAction
{
    use ActionTrait;
    protected TaskParticipantIterationLastReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationLastReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationLastReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
