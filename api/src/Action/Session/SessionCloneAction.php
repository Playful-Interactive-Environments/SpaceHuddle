<?php

namespace App\Action\Session;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Session\Service\SessionCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for cloning a session.
 *
 * @OA\Post(
 *   path="/session/{id}/clone",
 *   summary="Clones a session.",
 *   tags={"Session"},
 *   @OA\Parameter(in="path", name="id", description="ID of the session to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionCloneAction
{
    use AuthorisationActionTrait;
    protected SessionCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionCloner $service The service
     */
    public function __construct(Responder $responder, SessionCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
