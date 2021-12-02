<?php

namespace App\Action\Hierarchy;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Hierarchy\Service\HierarchyCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new hierarchy for the specified task.
 *
 * @OA\Post(
 *   path="/task/{taskId}/hierarchy/",
 *   summary="Create a new hierarchy for the task.",
 *   tags={"Hierarchy"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"keywords"},
 *         @OA\Property(property="keywords", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="parentId", type="string"),
 *         @OA\Property(property="order", type="number"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/HierarchyData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class HierarchyCreateAction
{
    use AuthorisationActionTrait;
    protected HierarchyCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param HierarchyCreator $service The service
     */
    public function __construct(Responder $responder, HierarchyCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
