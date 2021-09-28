<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the tasks  for the specified topic.
 *
 * @OA\Get(
 *   path="/topic/{topicId}/tasks/",
 *   summary="List of all tasks for the topic.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TaskData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskReadAllAction
{
    use ActionTrait;
    protected TaskReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskReader $service The service
     */
    public function __construct(Responder $responder, TaskReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
