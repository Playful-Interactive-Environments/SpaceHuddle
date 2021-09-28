<?php

namespace App\Action\Resource;

use App\Action\Base\ActionTrait;
use App\Domain\Resource\Service\ResourceReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the resources for the specified session.
 *
 * @OA\Get(
 *   path="/session/{sessionId}/resources/",
 *   summary="List of all resources for the session.",
 *   tags={"Resource"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ResourceData"))
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ResourceReadAllAction
{
    use ActionTrait;
    protected ResourceReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResourceReader $service The service
     */
    public function __construct(Responder $responder, ResourceReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
