<?php


namespace App\Action\User;

use App\Domain\User\Service\UserUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to change the password for the currently logged in user.
 *
 * @OA\Put(
 *   path="/user/",
 *   summary="Change the password of the logged-in user.",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"oldPassword", "password", "passwordConfirmation"},
 *         @OA\Property(property="oldPassword", type="string"),
 *         @OA\Property(property="password", type="string"),
 *         @OA\Property(property="passwordConfirmation", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class UserChangePasswordAction
{
    use UserSelfActionTrait;
    protected UserUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserUpdater $service The service
     */
    public function __construct(Responder $responder, UserUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
