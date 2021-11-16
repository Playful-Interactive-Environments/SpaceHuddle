<?php

namespace App\Action\Idea;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Idea\Service\IdeaCreatorTopic;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new idea for the specified topic.
 *
 * @OA\Post(
 *   path="/topic/{topicId}/idea/",
 *   summary="Create a new idea for the topic.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"keywords"},
 *         @OA\Property(property="keywords", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="link", type="string"),
 *         @OA\Property(property="image", type="string", format="binary"),
 *         @OA\Property(property="order", type="integer")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/IdeaData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaCreateForTopicAction
{
    use AuthorisationActionTrait;
    protected IdeaCreatorTopic $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaCreatorTopic $service The service
     */
    public function __construct(Responder $responder, IdeaCreatorTopic $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
