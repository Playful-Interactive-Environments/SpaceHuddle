<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\PublicScreenUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating the task to be displayed on the public screen for the session.
 *
 * @OA\Put(
 *   path="/api/session/{sessionId}/public_screen/{taskId}/",
 *   summary="Set a task to be displayed on the public screen for the session.",
 *   tags={"Public Screen"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session to be updated", required=true),
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task to be displayed on the public screen", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class PublicScreenUpdateAction
{
    use ActionTrait;
    protected PublicScreenUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param PublicScreenUpdater $service The service
     */
    public function __construct(Responder $responder, PublicScreenUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
