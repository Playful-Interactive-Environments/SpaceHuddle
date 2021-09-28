<?php


namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new topic for the specified session.
 *
 * @OA\Post(
 *   path="/session/{sessionId}/topic/",
 *   summary="Create a new topic for the session.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title", "description"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TopicData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicCreateAction
{
    use ActionTrait;
    protected TopicCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicCreator $service The service
     */
    public function __construct(Responder $responder, TopicCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
