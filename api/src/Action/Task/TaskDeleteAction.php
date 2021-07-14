<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a task.
 *
 * @OA\Delete(
 *   path="/api/task/{id}/",
 *   summary="Delete a task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of task to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskDeleteAction
{
    use ActionTrait;
    protected TaskDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskDeleter $service The service
     */
    public function __construct(Responder $responder, TaskDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
