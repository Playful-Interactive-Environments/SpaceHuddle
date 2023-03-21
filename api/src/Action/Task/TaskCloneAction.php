<?php

namespace App\Action\Task;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Task\Service\TaskCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for cloning a task.
 *
 * @OA\Post(
 *   path="/task/{id}/clone",
 *   summary="Clones a task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskCloneAction
{
    use AuthorisationActionTrait;
    protected TaskCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskCloner $service The service
     */
    public function __construct(Responder $responder, TaskCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
