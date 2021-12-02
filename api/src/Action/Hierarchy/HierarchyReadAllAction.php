<?php

namespace App\Action\Hierarchy;

use App\Action\Base\ActionTrait;
use App\Domain\Hierarchy\Service\HierarchyReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the ideas for the specified task and parent hierarchy.
 *
 * @OA\Get(
 *   path="/task/{taskId}/hierarchies/{parentHierarchyId}",
 *   summary="List of all ideas for the task and parent hierarchy.",
 *   tags={"Hierarchy"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Parameter(in="path", name="parentHierarchyId", description="ID of the parent hierarchy", required=false),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/HierarchyData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HierarchyReadAllAction
{
    use ActionTrait;
    protected HierarchyReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchyReader $service The service
     */
    public function __construct(Responder $responder, HierarchyReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
