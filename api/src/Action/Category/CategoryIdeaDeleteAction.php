<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryIdeaDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a list of ideas from a category.
 *
 * @OA\Delete(
 *   path="/api/category/{categoryId}/ideas/",
 *   summary="Delete the list of idea_ids from a category.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="categoryId", description="ID of the category", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(type="array",
 *         @OA\Items( type="string", example="uuid")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryIdeaDeleteAction
{
    use CategoryIdeaActionTrait;
    protected CategoryIdeaDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryIdeaDeleter $service The service
     */
    public function __construct(Responder $responder, CategoryIdeaDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
