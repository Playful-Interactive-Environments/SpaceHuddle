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
 *   path="/task/",
 *   summary="Update a task.",
 *   tags={"Task"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"id"},
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="taskType", type="string"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="parameter", type="object", format="json"),
 *         @OA\Property(property="order", type="integer"),
 *         @OA\Property(property="state", type="string"),
 *         @OA\Property(property="remainingTime", type="number", example=-1),
 *         @OA\Property(property="modules", type="array", @OA\Items(type="string"))
 *       )
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
