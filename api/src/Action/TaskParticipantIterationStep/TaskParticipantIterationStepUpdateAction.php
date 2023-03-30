<?php

namespace App\Action\TaskParticipantIterationStep;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIterationStep\Service\TaskParticipantIterationStepUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a task participant iteration step.
 *
 * @OA\Put(
 *   path="/task/{taskId}/participant_iteration/step/",
 *   summary="Update the iteration step of a authorized participant for a task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="state", ref="#/components/schemas/TaskParticipantIterationStepStateType"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationStepData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationStepUpdateAction
{
    use ActionTrait;
    protected TaskParticipantIterationStepUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationStepUpdater $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationStepUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
