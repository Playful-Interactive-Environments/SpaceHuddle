<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\PublicScreenReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the active task to be displayed on the public screen for the session.
 *
 * @OA\Get(
 *   path="/session/{sessionId}/public_screen/",
 *   summary="Get the aktive task to be displayed on the public screen for the session.",
 *   tags={"Public Screen"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session to be displayed", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TaskData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class PublicScreenReadAction
{
    use ActionTrait;
    protected PublicScreenReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param PublicScreenReader $service The service
     */
    public function __construct(Responder $responder, PublicScreenReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
