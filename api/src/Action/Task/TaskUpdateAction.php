<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a task.
 *
 * @OA\Put(
 *   path="/api/task/",
 *   summary="Update a task.",
 *   tags={"Task"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/TaskData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskUpdateAction
{
    use ActionTrait;
    protected TaskUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskUpdater $service The service
     */
    public function __construct(Responder $responder, TaskUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
