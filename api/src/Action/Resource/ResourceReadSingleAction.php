<?php

namespace App\Action\Resource;

use App\Action\Base\ActionTrait;
use App\Domain\Resource\Service\ResourceSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the resource with the specified id.
 *
 * @OA\Get(
 *   path="/resource/{id}/",
 *   summary="Detail data for the resource with the specified id.",
 *   tags={"Resource"},
 *   @OA\Parameter(in="path", name="id", description="ID of resource to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ResourceData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ResourceReadSingleAction
{
    use ActionTrait;
    protected ResourceSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResourceSingleReader $service The service
     */
    public function __construct(Responder $responder, ResourceSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
