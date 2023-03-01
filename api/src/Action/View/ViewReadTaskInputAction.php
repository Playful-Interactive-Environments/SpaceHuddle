<?php

namespace App\Action\View;

use App\Action\Base\ActionTrait;
use App\Domain\View\Service\ViewTaskInputReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the task input view data.
 *
 * @OA\Get(
 *   path="/task/{taskId}/input",
 *   summary="List of all input ideas for the task.",
 *   tags={"View"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Parameter(in="query", name="order", description="sort order",
 *     @OA\Schema(ref="#/components/schemas/IdeaSortOrder")),
 *   @OA\Parameter(in="query", name="refId", description="reference id"),
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
class ViewReadTaskInputAction
{
    use ActionTrait;
    protected ViewTaskInputReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ViewTaskInputReader $service The service
     */
    public function __construct(Responder $responder, ViewTaskInputReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
