<?php

namespace App\Action\Session;

use App\Action\Base\AbstractAction;
use App\Domain\Session\Service\SessionSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the session with the specified id.
 *
 * @OA\Get(
 *   path="/api/session/{id}/",
 *   summary="Detail data for the session with the specified id.",
 *   tags={"Session"},
 *   @OA\Parameter(in="path", name="id", description="ID of session to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionReadSingleAction extends AbstractAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionSingleReader $service The service
     */
    public function __construct(Responder $responder, SessionSingleReader $service)
    {
        parent::__construct($responder, $service);
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
