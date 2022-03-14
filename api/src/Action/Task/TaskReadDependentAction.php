<?php

namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskReaderDependent;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all dependent the tasks for the specified task id.
 *
 * @OA\Get(
 *   path="/task/{id}/dependent/",
 *   summary="List of all dependent the tasks for the specified task id.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
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
class TaskReadDependentAction
{
    use ActionTrait;
    protected TaskReaderDependent $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskReaderDependent $service The service
     */
    public function __construct(Responder $responder, TaskReaderDependent $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
