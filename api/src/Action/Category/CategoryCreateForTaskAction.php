<?php

namespace App\Action\Category;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Category\Service\CategoryCreatorTask;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new category for the specified task.
 *
 * @OA\Post(
 *   path="/task/{taskId}/category/",
 *   summary="Create a new category for the task.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"keywords"},
 *         @OA\Property(property="keywords", type="string"),
 *         @OA\Property(property="description", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/CategoryData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryCreateForTaskAction
{
    use AuthorisationActionTrait;
    protected CategoryCreatorTask $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryCreatorTask $service The service
     */
    public function __construct(Responder $responder, CategoryCreatorTask $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
