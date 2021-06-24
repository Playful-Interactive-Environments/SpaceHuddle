<?php

namespace App\Action\Participant;

use App\Action\Base\ActionTrait;
use App\Domain\Participant\Service\ParticipantTopicReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all topics for the currently logged-in participant.
 *
 * @OA\Get(
 *   path="/api/participant/topics/",
 *   summary="List of all topics for the logged-in participant.",
 *   tags={"Participant"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TopicData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantReadTopicAction
{
    use ActionTrait;
    protected ParticipantTopicReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantTopicReader $service The service
     */
    public function __construct(Responder $responder, ParticipantTopicReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
