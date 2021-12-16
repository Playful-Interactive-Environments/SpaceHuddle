<?php


namespace App\Action\User;

use App\Domain\User\Service\UserSendConfirm;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to send a mail to confirm the email.
 *
 * @OA\Put(
 *   path="/user/send-confirm/{email}/",
 *   summary="Send a mail to confirm the email",
 *   tags={"User"},
 *   @OA\Parameter(
 *     in="path",
 *     name="email",
 *     description="email address witch should be confirmed",
 *     required=true
 *     ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found")
 * )
 */
class UserSendConfirmAction
{
    use UserSelfActionTrait;
    protected UserSendConfirm $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserSendConfirm $service The service
     */
    public function __construct(Responder $responder, UserSendConfirm $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
