<?php

namespace App\Action\Resource;

use App\Action\Base\ActionTrait;
use App\Domain\Resource\Service\ResourceUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a resource.
 *
 * @OA\Put(
 *   path="/api/resource/",
 *   summary="Update a resource.",
 *   tags={"Resource"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/ResourceData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ResourceData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ResourceUpdateAction
{
    use ActionTrait;
    protected ResourceUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResourceUpdater $service The service
     */
    public function __construct(Responder $responder, ResourceUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
