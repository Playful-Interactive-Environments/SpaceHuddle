<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Domain\Category\Service\CategoryReaderTopic;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the categories for the specified topic.
 *
 * @OA\Get(
 *   path="/api/topic/{topicId}/categories/",
 *   summary="List of all categories for the topic.",
 *   tags={"Category"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/CategoryData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class CategoryReadAllFromTopicAction
{
    use ActionTrait;
    protected CategoryReaderTopic $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param CategoryReaderTopic $service The service
     */
    public function __construct(Responder $responder, CategoryReaderTopic $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }

}
