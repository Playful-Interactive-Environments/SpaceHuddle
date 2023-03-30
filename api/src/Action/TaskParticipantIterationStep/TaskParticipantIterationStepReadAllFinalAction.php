<?php

namespace App\Action\TaskParticipantIterationStep;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantIterationStep\Service\TaskParticipantIterationStepFinalReader;
use App\Domain\TaskParticipantIterationStep\Service\TaskParticipantIterationStepReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all final participant iterations steps for the specified task.
 *
 * @OA\Get(
 *   path="/task/{id}/participant_iteration/step/final/",
 *   summary="List of all final participant iterations steps for the task.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the task", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TaskParticipantIterationStepData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantIterationStepReadAllFinalAction
{
    use ActionTrait;
    protected TaskParticipantIterationStepFinalReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantIterationStepFinalReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantIterationStepFinalReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
