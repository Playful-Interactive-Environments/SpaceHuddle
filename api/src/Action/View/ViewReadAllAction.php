<?php

namespace App\Action\View;

use App\Action\Base\ActionTrait;
use App\Domain\View\Service\ViewReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the views for the specified topic.
 *
 * @OA\Get(
 *   path="/topic/{topicId}/views",
 *   summary="List of all views for the topic.",
 *   tags={"View"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ViewData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ViewReadAllAction
{
    use ActionTrait;
    protected ViewReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ViewReader $service The service
     */
    public function __construct(Responder $responder, ViewReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
