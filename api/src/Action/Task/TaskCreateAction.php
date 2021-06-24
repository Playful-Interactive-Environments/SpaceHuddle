<?php


namespace App\Action\Task;

use App\Action\Base\ActionTrait;
use App\Domain\Task\Service\TaskCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new task for the specified session.
 *
 * @OA\Post(
 *   path="/api/topic/{topicId}/task/",
 *   summary="Create a new task for the topic.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"taskType"},
 *         @OA\Property(property="taskType", type="string"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="parameter", type="object", format="json"),
 *         @OA\Property(property="order", type="integer")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskCreateAction
{
    use ActionTrait;
    protected TaskCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskCreator $service The service
     */
    public function __construct(Responder $responder, TaskCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
