<?php

namespace App\Action\Selection;

use App\Domain\Selection\Service\SelectionIdeaDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a list of ideas from a selection.
 *
 * @OA\Delete(
 *   path="/selection/{selectionId}/ideas/",
 *   summary="Delete the list of idea_ids from a selection.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="selectionId", description="ID of the selection", required=true),
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
class SelectionIdeaDeleteAction
{
    use SelectionIdeaActionTrait;
    protected SelectionIdeaDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionIdeaDeleter $service The service
     */
    public function __construct(Responder $responder, SelectionIdeaDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
