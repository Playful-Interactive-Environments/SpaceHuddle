<?php

namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a session role.
 *
 * @OA\Delete(
 *   path="/session/{sessionId}/authorized_user/{username}/",
 *   summary="Remove username for a session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Parameter(in="path", name="username", description="Username of the user who should be deprived of the
 *   session permission", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionRoleDeleteAction
{
    use ActionTrait;
    protected SessionRoleDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleDeleter $service The service
     */
    public function __construct(Responder $responder, SessionRoleDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
