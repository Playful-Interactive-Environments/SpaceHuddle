<?php

namespace App\Action\Participant;

use App\Action\Base\ActionTrait;
use App\Domain\Participant\Service\ParticipantTopicTaskReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all topic tasks for the currently logged-in participant.
 *
 * @OA\Get(
 *   path="/api/topic/{topicId}/participant_tasks/",
 *   summary="List of all topic tasks for the logged-in participant.",
 *   tags={"Participant"},
 *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TaskData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantReadTopicTaskAction
{
    use ActionTrait;
    protected ParticipantTopicTaskReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantTopicTaskReader $service The service
     */
    public function __construct(Responder $responder, ParticipantTopicTaskReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
