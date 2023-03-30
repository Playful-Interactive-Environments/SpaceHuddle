<?php


namespace App\Action\TaskParticipantIterationStep;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIterationStep\Service\TaskParticipantIterationStepCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new participant iteration step for the specified task.
 *
 * @OA\Post(
 *   path="/task/{id}/participant_iteration/step/",
 *   summary="Create a new participant iteration step for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title", "description"},
 *         @OA\Property(property="iteration", type="int"),
 *         @OA\Property(property="ideaId", type="string"),
 *         @OA\Property(property="state", ref="#/components/schemas/TaskParticipantIterationStepStateType"),
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskParticipantIterationStepData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationStepCreateAction
{
    use ActionTrait;
    protected TaskParticipantIterationStepCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationStepCreator $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationStepCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
