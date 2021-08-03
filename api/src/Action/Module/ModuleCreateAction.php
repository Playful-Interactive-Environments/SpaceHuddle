<?php


namespace App\Action\Module;

use App\Action\Base\ActionTrait;
use App\Domain\Module\Service\ModuleCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new module for the specified task.
 *
 * @OA\Post(
 *   path="/api/task/{taskId}/module/",
 *   summary="Create a new module for the task.",
 *   tags={"Module"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"name"},
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="order", type="integer"),
 *         @OA\Property(property="state", type="string"),
 *         @OA\Property(property="parameter", type="object", format="json"),
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ModuleData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ModuleCreateAction
{
    use ActionTrait;
    protected ModuleCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ModuleCreator $service The service
     */
    public function __construct(Responder $responder, ModuleCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
