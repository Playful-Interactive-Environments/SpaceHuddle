<?php

namespace App\Action\Category;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Category\Service\CategoryCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for cloning a category.
 *
 * @OA\Post(
 *   path="/category/{id}/clone",
 *   summary="Clones a category.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="id", description="ID of the category to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/CategoryData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryCloneAction
{
    use AuthorisationActionTrait;
    protected CategoryCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryCloner $service The service
     */
    public function __construct(Responder $responder, CategoryCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
