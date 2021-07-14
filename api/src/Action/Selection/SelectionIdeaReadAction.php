<?php

namespace App\Action\Selection;

use App\Action\Base\ActionTrait;
use App\Domain\Selection\Service\SelectionIdeaReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to read a list of ideas associated with the selection ID.
 *
 * @OA\Get(
 *   path="/api/selection/{selectionId}/ideas",
 *   summary="Ideas for the selection with the specified id.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="selectionId", description="ID of selection to return", required=true),
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
class SelectionIdeaReadAction
{
    use ActionTrait;
    protected SelectionIdeaReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionIdeaReader $service The service
     */
    public function __construct(Responder $responder, SelectionIdeaReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
