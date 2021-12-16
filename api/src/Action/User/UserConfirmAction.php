<?php

namespace App\Action\User;

use App\Action\Base\ActionTrait;
use App\Domain\User\Service\UserConfirm;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for confirming a email.
 *
 * @OA\Put(
 *   path="/user/confirm/{token}/",
 *   summary="Confirm the email address",
 *   tags={"User"},
 *   @OA\Parameter(
 *     in="path",
 *     name="token",
 *     description="confirm token",
 *     required=true
 *     ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 * )
 */
final class UserConfirmAction
{
    use ActionTrait;
    protected UserConfirm $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserConfirm $service The service
     */
    public function __construct(Responder $responder, UserConfirm $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
