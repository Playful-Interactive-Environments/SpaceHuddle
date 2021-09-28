<?php

namespace App\Action\Selection;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Selection\Service\SelectionCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new selection for the specified topic.
 *
 * @OA\Post(
 *   path="/topic/{topicId}/selection/",
 *   summary="Create a new selection for the topic.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"name"},
 *         @OA\Property(property="name", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SelectionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SelectionCreateAction
{
    use AuthorisationActionTrait;
    protected SelectionCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionCreator $service The service
     */
    public function __construct(Responder $responder, SelectionCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
