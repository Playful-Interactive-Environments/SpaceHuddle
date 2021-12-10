<?php

namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\UsedModuleNameReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the modules for the specified task.
 *
 * @OA\Get(
 *   path="/module_names/{taskType}",
 *   summary="List of all used module names from the user for the task type.",
 *   tags={"Module"},
 *   @OA\Parameter(in="path", name="taskType", description="the task type looking for", required=true,
 *     @OA\Schema(ref="#/components/schemas/TaskType")),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(type="string"))
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class UsedModuleNamesReadAction
{
    use ActionTrait;
    protected UsedModuleNameReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UsedModuleNameReader $service The service
     */
    public function __construct(Responder $responder, UsedModuleNameReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
