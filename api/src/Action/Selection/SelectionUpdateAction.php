<?php

namespace App\Action\Selection;

use App\Action\Base\ActionTrait;
use App\Domain\Selection\Service\SelectionUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a selection.
 *
 * @OA\Put(
 *   path="/api/selection/",
 *   summary="Update a selection.",
 *   tags={"Selection"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/SelectionData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SelectionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SelectionUpdateAction
{
    use ActionTrait;
    protected SelectionUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionUpdater $service The service
     */
    public function __construct(Responder $responder, SelectionUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
