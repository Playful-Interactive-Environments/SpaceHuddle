<?php

namespace App\Action\Hierarchy;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Hierarchy\Service\HierarchyCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for cloning a hierarchy.
 *
 * @OA\Post(
 *   path="/hierarchy/{id}/clone",
 *   summary="Clones a hierarchy.",
 *   tags={"Hierarchy"},
 *   @OA\Parameter(in="path", name="id", description="ID of the hierarchy to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/HierarchyData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HiearchyCloneAction
{
    use AuthorisationActionTrait;
    protected HierarchyCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchyCloner $service The service
     */
    public function __construct(Responder $responder, HierarchyCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
