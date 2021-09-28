<?php

namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a topic.
 *
 * @OA\Put(
 *   path="/topic/",
 *   summary="Update a topic.",
 *   tags={"Topic"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/TopicData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TopicData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicUpdateAction
{
    use ActionTrait;
    protected TopicUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicUpdater $service The service
     */
    public function __construct(Responder $responder, TopicUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
