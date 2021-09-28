<?php


namespace App\Action\SessionRole;

use App\Action\Base\ActionTrait;
use App\Domain\SessionRole\Service\SessionRoleCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new session role for the specified session.
 *
 * @OA\Post(
 *   path="/session/{sessionId}/authorized_user/",
 *   summary="Add a new authorized user to the session.",
 *   tags={"Session Role"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"username", "role"},
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
class SessionRoleCreateAction
{
    use ActionTrait;
    protected SessionRoleCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionRoleCreator $service The service
     */
    public function __construct(Responder $responder, SessionRoleCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
