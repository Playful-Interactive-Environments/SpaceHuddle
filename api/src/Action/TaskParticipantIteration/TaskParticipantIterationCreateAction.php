<?php


namespace App\Action\TaskParticipantIteration;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIteration\Service\TaskParticipantIterationCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new participant iteration for the specified task.
 *
 * @OA\Post(
 *   path="/task/{id}/participant_iteration/",
 *   summary="Create a new participant iteration for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title", "description"},
 *         @OA\Property(property="state", ref="#/components/schemas/TaskParticipantIterationStateType"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationCreateAction
{
    use ActionTrait;
    protected TaskParticipantIterationCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationCreator $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
