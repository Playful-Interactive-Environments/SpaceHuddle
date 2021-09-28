<?php

namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\ModuleDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a module.
 *
 * @OA\Delete(
 *   path="/module/{id}/",
 *   summary="Delete a module.",
 *   tags={"Module"},
 *   @OA\Parameter(in="path", name="id", description="ID of module to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ModuleDeleteAction
{
    use ActionTrait;
    protected ModuleDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ModuleDeleter $service The service
     */
    public function __construct(Responder $responder, ModuleDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
