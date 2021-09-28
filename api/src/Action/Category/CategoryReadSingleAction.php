<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategorySingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the category with the specified id.
 *
 * @OA\Get(
 *   path="/category/{id}/",
 *   summary="Detail data for the category with the specified id.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="id", description="ID of category to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/CategoryData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryReadSingleAction
{
    use ActionTrait;
    protected CategorySingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategorySingleReader $service The service
     */
    public function __construct(Responder $responder, CategorySingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
