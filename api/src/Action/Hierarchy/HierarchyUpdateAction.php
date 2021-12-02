<?php

namespace App\Action\Hierarchy;

use App\Action\Base\ActionTrait;
use App\Domain\Hierarchy\Service\HierarchyUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a hierarchy.
 *
 * @OA\Put(
 *   path="/hierarchy/",
 *   summary="Update a hierarchy.",
 *   tags={"Hierarchy"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/HierarchyData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/HierarchyData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HierarchyUpdateAction
{
    use ActionTrait;
    protected HierarchyUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchyUpdater $service The service
     */
    public function __construct(Responder $responder, HierarchyUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
