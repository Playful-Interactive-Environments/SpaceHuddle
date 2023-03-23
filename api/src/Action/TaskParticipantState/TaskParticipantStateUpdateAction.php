<?php

namespace App\Action\TaskParticipantState;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleUpdater;
use App\Domain\TaskParticipantState\Service\TaskParticipantStateUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a task participant state.
 *
 * @OA\Put(
 *   path="/task/{taskId}/participant_state/",
 *   summary="Update the state of a authorized participant for a task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         @OA\Property(property="count", type="int"),
 *         @OA\Property(property="state", ref="#/components/schemas/TaskParticipantStateType"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantStateData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantStateUpdateAction
{
    use ActionTrait;
    protected TaskParticipantStateUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantStateUpdater $service The service
     */
    public function __construct(Responder $responder, TaskParticipantStateUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
