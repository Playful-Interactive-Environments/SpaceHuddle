<?php

namespace App\Action\Participant;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Participant\Service\ParticipantTaskReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the tasks for the currently logged-in participant.
 *
 * @OA\Get(
 *   path="/api/participant/tasks/",
 *   summary="List of all tasks for the logged-in participant.",
 *   tags={"Participant"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ParticipantTaskData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantReadTaskAction
{
    use AuthorisationActionTrait;
    protected ParticipantTaskReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantTaskReader $service The service
     */
    public function __construct(Responder $responder, ParticipantTaskReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
