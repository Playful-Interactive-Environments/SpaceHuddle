<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the sessions for which the user is authorized.
 *
 * @OA\Get(
 *   path="/api/sessions/",
 *   summary="List of all sessions for which the user is authorized.",
 *   tags={"Session"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   @OA\Response(response="401", description="Unauthorized"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionReadAllAction
{
    use SessionActionTrait;
    protected SessionReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionReader $service The service
     */
    public function __construct(Responder $responder, SessionReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
