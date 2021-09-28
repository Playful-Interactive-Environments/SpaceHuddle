<?php

namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\ModuleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the modules for the specified task.
 *
 * @OA\Get(
 *   path="/task/{taskId}/modules/",
 *   summary="List of all modules for the task.",
 *   tags={"Module"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ModuleData"))
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ModuleReadAllAction
{
    use ActionTrait;
    protected ModuleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ModuleReader $service The service
     */
    public function __construct(Responder $responder, ModuleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
