<?php


namespace App\Action\User;

use App\Domain\User\Service\UserReset;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to send a mail to reset the password.
 *
 * @OA\Put(
 *   path="/user/reset/{email}/",
 *   summary="Send a mail to reset the password for the email",
 *   tags={"User"},
 *   @OA\Parameter(
 *     in="path",
 *     name="email",
 *     description="email address for which the password should be reset",
 *     required=true
 *     ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class UserResetPasswordAction
{
    use UserSelfActionTrait;
    protected UserReset $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserReset $service The service
     */
    public function __construct(Responder $responder, UserReset $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
