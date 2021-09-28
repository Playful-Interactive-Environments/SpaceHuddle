<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaReaderTopic;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the ideas for the specified topic.
 *
 * @OA\Get(
 *   path="/topic/{topicId}/ideas/",
 *   summary="List of all ideas for the topic.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Parameter(in="query", name="order", description="sort order",
 *     @OA\Schema(ref="#/components/schemas/IdeaSortOrder")),
 *   @OA\Parameter(in="query", name="refId", description="reference id"),
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
class IdeaReadAllFromTopicAction
{
    use ActionTrait;
    protected IdeaReaderTopic $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaReaderTopic $service The service
     */
    public function __construct(Responder $responder, IdeaReaderTopic $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
