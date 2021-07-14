<?php

namespace App\Action\Resource;

use App\Action\Base\ActionTrait;
use App\Domain\Resource\Service\ResourceDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a resource.
 *
 * @OA\Delete(
 *   path="/api/resource/{id}/",
 *   summary="Delete a resource.",
 *   tags={"Resource"},
 *   @OA\Parameter(in="path", name="id", description="ID of resource to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ResourceDeleteAction
{
    use ActionTrait;
    protected ResourceDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResourceDeleter $service The service
     */
    public function __construct(Responder $responder, ResourceDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
