<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Data\AuthorisationData;
use App\Domain\Category\Service\CategoryIdeaCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for adding a list of ideas to a category.
 *
 * @OA\Post(
 *   path="/api/category/{categoryId}/ideas/",
 *   summary="Add list of idea_ids to a category.",
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
class CategoryIdeaAddAction
{
    use CategoryIdeaActionTrait;
    protected CategoryIdeaCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryIdeaCreator $service The service
     */
    public function __construct(Responder $responder, CategoryIdeaCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
