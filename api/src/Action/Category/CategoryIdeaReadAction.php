<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryIdeaReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to read a list of ideas associated with the category ID.
 *
 * @OA\Get(
 *   path="/category/{categoryId}/ideas",
 *   summary="Ideas for the category with the specified id.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="categoryId", description="ID of category to return", required=true),
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
class CategoryIdeaReadAction
{
    use ActionTrait;
    protected CategoryIdeaReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryIdeaReader $service The service
     */
    public function __construct(Responder $responder, CategoryIdeaReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
