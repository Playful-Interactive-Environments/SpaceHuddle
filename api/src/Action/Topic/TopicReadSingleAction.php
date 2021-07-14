<?php

namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the topic with the specified id.
 *
 * @OA\Get(
 *   path="/api/topic/{id}/",
 *   summary="Detail data for the topic with the specified id.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="id", description="ID of topic to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TopicData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicReadSingleAction
{
    use ActionTrait;
    protected TopicSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicSingleReader $service The service
     */
    public function __construct(Responder $responder, TopicSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
