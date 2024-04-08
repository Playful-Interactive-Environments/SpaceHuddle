<?php

namespace App\Action\View;

use App\Action\Base\ActionTrait;
use App\Domain\View\Service\ViewSessionReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the views for the specified session.
 *
 * @OA\Get(
 *   path="/session/{sessionId}/views",
 *   summary="List of all views for the session.",
 *   tags={"View"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
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
class ViewReadSessionAction
{
    use ActionTrait;
    protected ViewSessionReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ViewSessionReader $service The service
     */
    public function __construct(Responder $responder, ViewSessionReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
