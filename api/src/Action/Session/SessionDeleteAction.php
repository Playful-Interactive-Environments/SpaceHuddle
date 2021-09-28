<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a session.
 * @OA\Delete(
 *   path="/session/{id}/",
 *   summary="Delete a session.",
 *   tags={"Session"},
 *   @OA\Parameter(in="path", name="id", description="ID of session to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionDeleteAction
{
    use ActionTrait;
    protected SessionDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionDeleter $service The service
     */
    public function __construct(Responder $responder, SessionDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
