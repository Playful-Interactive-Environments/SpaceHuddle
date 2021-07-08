<?php

namespace App\Action\Selection;

use App\Action\Base\ActionTrait;
use App\Domain\Selection\Service\SelectionReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the selections for the specified topic.
 *
 * @OA\Get(
 *   path="/api/topic/{topicId}/selections/",
 *   summary="List of all selections for the topic.",
 *   tags={"Selection"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SelectionData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SelectionReadAllAction
{
    use ActionTrait;
    protected SelectionReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SelectionReader $service The service
     */
    public function __construct(Responder $responder, SelectionReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
