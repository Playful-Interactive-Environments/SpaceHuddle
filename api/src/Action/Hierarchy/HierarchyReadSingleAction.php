<?php

namespace App\Action\Hierarchy;

use App\Action\Base\ActionTrait;
use App\Domain\Hierarchy\Service\HierarchySingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the hierarchy with the specified id.
 *
 * @OA\Get(
 *   path="/hierarchy/{id}/",
 *   summary="Detail data for the hierarchy with the specified id.",
 *   tags={"Hierarchy"},
 *   @OA\Parameter(in="path", name="id", description="ID of hierarchy to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/HierarchyData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HierarchyReadSingleAction
{
    use ActionTrait;
    protected HierarchySingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchySingleReader $service The service
     */
    public function __construct(Responder $responder, HierarchySingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
