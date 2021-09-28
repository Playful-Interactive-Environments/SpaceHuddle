<?php

namespace App\Action\Selection;

use App\Action\Base\ActionTrait;
use App\Domain\Selection\Service\SelectionDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a selection.
 *
 * @OA\Delete(
 *   path="/selection/{id}/",
 *   summary="Delete a selection.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="id", description="ID of selection to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SelectionDeleteAction
{
    use ActionTrait;
    protected SelectionDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionDeleter $service The service
     */
    public function __construct(Responder $responder, SelectionDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
