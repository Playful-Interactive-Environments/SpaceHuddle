<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskStateUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating the participant application state for a task.
 *
 * @OA\Put(
 *   path="/task/{taskId}/client_application_state/{state}/",
 *   summary="Set the participant application state for the task.",
 *   tags={"Client Application"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task to be updated", required=true),
 *   @OA\Parameter(in="path", name="state",
 *     description="display status on the participant devices",
 *     required=true,
 *     @OA\Schema(ref="#/components/schemas/TaskState")),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskStateUpdateAction
{
    use ActionTrait;
    protected TaskStateUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskStateUpdater $service The service
     */
    public function __construct(Responder $responder, TaskStateUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
