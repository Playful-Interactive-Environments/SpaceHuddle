<?php

namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\ModuleSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the module with the specified id.
 *
 * @OA\Get(
 *   path="/api/module/{id}/",
 *   summary="Detail data for the module with the specified id.",
 *   tags={"Module"},
 *   @OA\Parameter(in="path", name="id", description="ID of module to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ModuleData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ModuleReadSingleAction
{
    use ActionTrait;
    protected ModuleSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ModuleSingleReader $service The service
     */
    public function __construct(Responder $responder, ModuleSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
