<?php

namespace App\Action\TaskParticipantState;

use App\Action\Base\ActionTrait;
use App\Domain\TaskParticipantState\Service\TaskParticipantStateFromTopicReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the participant states for the specified topic.
 *
 * @OA\Get(
 *   path="/topic/{id}/participant_state/",
 *   summary="List of all participant states for the topic.",
 *   tags={"Task"},
 *   @OA\Parameter(in="path", name="id", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *       @OA\Schema(required={"id"},
 *         @OA\Property(property="taskId", type="string"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="count", type="integer")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TaskParticipantStateReadAllFromTopicAction
{
    use ActionTrait;
    protected TaskParticipantStateFromTopicReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TaskParticipantStateFromTopicReader $service The service
     */
    public function __construct(Responder $responder, TaskParticipantStateFromTopicReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
