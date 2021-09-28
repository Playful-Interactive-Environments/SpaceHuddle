<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the task with the specified id.
 *
 * @OA\Get(
 *   path="/task/{id}/",
 *   summary="Detail data for the task with the specified id.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of task to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskReadSingleAction
{
    use ActionTrait;
    protected TaskSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskSingleReader $service The service
     */
    public function __construct(Responder $responder, TaskSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
