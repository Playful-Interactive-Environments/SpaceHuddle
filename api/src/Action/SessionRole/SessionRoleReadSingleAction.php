<?php

namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the session role with the specified id.
 *
 * @OA\Get(
 *   path="/api/session/{sessionId}/own_user_role/",
 *   summary="Get the role of the username in the session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionRoleData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionRoleReadSingleAction
{
    use ActionTrait;
    protected SessionRoleSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleSingleReader $service The service
     */
    public function __construct(Responder $responder, SessionRoleSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
