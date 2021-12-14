<?php

namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleDeleterOwn;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a session role.
 *
 * @OA\Delete(
 *   path="/session/{sessionId}/own_user_role/",
 *   summary="Remove own user from a session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionRoleDeleteOwnAction
{
    use ActionTrait;
    protected SessionRoleDeleterOwn $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleDeleterOwn $service The service
     */
    public function __construct(Responder $responder, SessionRoleDeleterOwn $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
