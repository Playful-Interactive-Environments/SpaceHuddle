<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryReaderTask;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the categories for the specified task.
 *
 * @OA\Get(
 *   path="/api/task/{taskId}/categories/",
 *   summary="List of all categories for the task.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/CategoryData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryReadAllFromTaskAction
{
    use ActionTrait;
    protected CategoryReaderTask $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryReaderTask $service The service
     */
    public function __construct(Responder $responder, CategoryReaderTask $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
