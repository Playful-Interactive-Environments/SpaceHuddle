<?php

namespace App\Action\Hierarchy;

use App\Action\Base\ActionTrait;
use App\Domain\Hierarchy\Service\HierarchyDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a hierarchy.
 *
 * @OA\Delete(
 *   path="/hierarchy/{id}/",
 *   summary="Delete a hierarchy.",
 *   tags={"Hierarchy"},
 *   @OA\Parameter(in="path", name="id", description="ID of hierarchy to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HierarchyDeleteAction
{
    use ActionTrait;
    protected HierarchyDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchyDeleter $service The service
     */
    public function __construct(Responder $responder, HierarchyDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
