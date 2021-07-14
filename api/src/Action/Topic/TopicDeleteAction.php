<?php

namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a topic.
 *
 * @OA\Delete(
 *   path="/api/topic/{id}/",
 *   summary="Delete a topic.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="id", description="ID of topic to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicDeleteAction
{
    use ActionTrait;
    protected TopicDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicDeleter $service The service
     */
    public function __construct(Responder $responder, TopicDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
