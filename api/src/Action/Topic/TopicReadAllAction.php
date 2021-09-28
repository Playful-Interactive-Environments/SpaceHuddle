<?php

namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the topics  for the specified session.
 *
 * @OA\Get(
 *   path="/session/{sessionId}/topics/",
 *   summary="List of all topics for the session.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TopicData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicReadAllAction
{
    use ActionTrait;
    protected TopicReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicReader $service The service
     */
    public function __construct(Responder $responder, TopicReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
