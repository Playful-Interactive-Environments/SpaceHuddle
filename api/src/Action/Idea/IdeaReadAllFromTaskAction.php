<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaReaderTask;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the ideas for the specified task.
 *
 * @OA\Get(
 *   path="/api/task/{taskId}/ideas/",
 *   summary="List of all ideas for the task.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Parameter(in="query", name="order", description="sort order",
 *     @OA\Schema(ref="#/components/schemas/IdeaSortOrder")),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/IdeaData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaReadAllFromTaskAction
{
    use ActionTrait;
    protected IdeaReaderTask $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaReaderTask $service The service
     */
    public function __construct(Responder $responder, IdeaReaderTask $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
