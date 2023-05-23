<?php

namespace App\Action\Participant;

use App\Domain\Participant\Service\ParticipantUpdater;
use App\Domain\Participant\Service\ParticipantUpdaterParameter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action that sets participant parameter.
 *
 * @OA\Put(
 *   path="/participant/parameter/",
 *   summary="Set the participant parameter.",
 *   tags={"Participant"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"parameter"},
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ParticipantUpdateParameterAction
{
    use ParticipantSelfActionTrait;
    protected ParticipantUpdaterParameter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ParticipantUpdaterParameter $service The service
     */
    public function __construct(Responder $responder, ParticipantUpdaterParameter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
