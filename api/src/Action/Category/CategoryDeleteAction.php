<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a category.
 *
 * @OA\Delete(
 *   path="/api/category/{id}/",
 *   summary="Delete a category.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="id", description="ID of category to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryDeleteAction
{
    use ActionTrait;
    protected CategoryDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryDeleter $service The service
     */
    public function __construct(Responder $responder, CategoryDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
