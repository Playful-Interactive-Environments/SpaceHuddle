<?php

namespace App\Action\Selection;

use App\Action\Base\ActionTrait;
use App\Domain\Selection\Service\SelectionSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the selection with the specified id.
 *
 * @OA\Get(
 *   path="/api/selection/{id}/",
 *   summary="Detail data for the selection with the specified id.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="id", description="ID of selection to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SelectionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SelectionReadSingleAction
{
    use ActionTrait;
    protected SelectionSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionSingleReader $service The service
     */
    public function __construct(Responder $responder, SelectionSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
