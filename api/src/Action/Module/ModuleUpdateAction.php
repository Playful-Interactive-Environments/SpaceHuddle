<?php

namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\ModuleUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a module.
 *
 * @OA\Put(
 *   path="/module/",
 *   summary="Update a module.",
 *   tags={"Module"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/ModuleData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ModuleData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ModuleUpdateAction
{
    use ActionTrait;
    protected ModuleUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ModuleUpdater $service The service
     */
    public function __construct(Responder $responder, ModuleUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
