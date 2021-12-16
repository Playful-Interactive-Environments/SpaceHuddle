<?php


namespace App\Action\User;

use App\Domain\User\Service\UserForgetPassword;
use App\Domain\User\Service\UserUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to change the password for the the user token.
 *
 * @OA\Put(
 *   path="/user/forget-password/",
 *   summary="Change the password for the user token.",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"token", "password", "passwordConfirmation"},
 *         @OA\Property(property="token", type="string"),
 *         @OA\Property(property="password", type="string"),
 *         @OA\Property(property="passwordConfirmation", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found")
 * )
 */
class UserForgetPasswordAction
{
    use UserSelfActionTrait;
    protected UserForgetPassword $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserForgetPassword $service The service
     */
    public function __construct(Responder $responder, UserForgetPassword $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
