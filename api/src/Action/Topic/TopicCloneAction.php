<?php

namespace App\Action\Topic;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Topic\Service\TopicCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new session.
 *
 * @OA\Post(
 *   path="/topic/{topicId}/clone",
 *   summary="Clones a topic.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the topic to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TopicData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicCloneAction
{
    use AuthorisationActionTrait;
    protected TopicCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicCloner $service The service
     */
    public function __construct(Responder $responder, TopicCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
