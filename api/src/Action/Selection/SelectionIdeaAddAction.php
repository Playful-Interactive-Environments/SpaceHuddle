<?php

namespace App\Action\Selection;

use App\Domain\Selection\Service\SelectionIdeaCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for adding a list of ideas to a selection.
 *
 * @OA\Post(
 *   path="/selection/{selectionId}/ideas/",
 *   summary="Add list of idea_ids to a selection.",
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
class SelectionIdeaAddAction
{
    use SelectionIdeaActionTrait;
    protected SelectionIdeaCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionIdeaCreator $service The service
     */
    public function __construct(Responder $responder, SelectionIdeaCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
