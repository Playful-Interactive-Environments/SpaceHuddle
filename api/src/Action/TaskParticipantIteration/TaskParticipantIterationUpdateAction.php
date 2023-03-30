<?php

namespace App\Action\TaskParticipantIteration;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIteration\Service\TaskParticipantIterationUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a task participant iteration.
 *
 * @OA\Put(
 *   path="/task/{taskId}/participant_iteration/",
 *   summary="Update the iteration of a authorized participant for a task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="state", ref="#/components/schemas/TaskParticipantIterationStateType"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationUpdateAction
{
    use ActionTrait;
    protected TaskParticipantIterationUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationUpdater $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
