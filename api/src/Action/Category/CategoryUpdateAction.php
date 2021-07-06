<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a category.
 *
 * @OA\Put(
 *   path="/api/category/",
 *   summary="Update a category.",
 *   tags={"Category"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/CategoryData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/CategoryData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryUpdateAction
{
    use ActionTrait;
    protected CategoryUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryUpdater $service The service
     */
    public function __construct(Responder $responder, CategoryUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
