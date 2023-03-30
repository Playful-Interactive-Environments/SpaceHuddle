<?php

namespace App\Action\TaskParticipantIterationStep;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIterationStep\Service\TaskParticipantIterationStepLastReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the last participant iterations step for the specified task.
 *
 * @OA\Get(
 *   path="/task/{id}/participant_iteration/step/last/",
 *   summary="Read the last participant iterations step for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of task to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationStepData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationStepReadLastAction
{
    use ActionTrait;
    protected TaskParticipantIterationStepLastReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationStepLastReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationStepLastReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
