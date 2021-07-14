<?php

namespace App\Action\Category;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Category\Service\CategoryCreatorTopic;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new category for the specified topic.
 *
 * @OA\Post(
 *   path="/api/topic/{topicId}/category/",
 *   summary="Create a new category for the topic.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"keywords"},
 *         @OA\Property(property="keywords", type="string"),
 *         @OA\Property(property="description", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/CategoryData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryCreateForTopicAction
{
    use AuthorisationActionTrait;
    protected CategoryCreatorTopic $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryCreatorTopic $service The service
     */
    public function __construct(Responder $responder, CategoryCreatorTopic $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
