<?php

namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the session roles for the specified session.
 *
 * @OA\Get(
 *   path="/api/session/{sessionId}/authorized_users/",
 *   summary="List of all authorized users for the session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionRoleData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionRoleReadAllAction
{
    use ActionTrait;
    protected SessionRoleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleReader $service The service
     */
    public function __construct(Responder $responder, SessionRoleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
