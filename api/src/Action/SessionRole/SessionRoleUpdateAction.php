<?php

namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a session role.
 *
 * @OA\Put(
 *   path="/api/session/{sessionId}/authorized_user/",
 *   summary="Update the role of a authorized user for a session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"id"},
 *         @OA\Property(property="username", type="string"),
 *         @OA\Property(property="role", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionRoleData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionRoleUpdateAction
{
    use ActionTrait;
    protected SessionRoleUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleUpdater $service The service
     */
    public function __construct(Responder $responder, SessionRoleUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
