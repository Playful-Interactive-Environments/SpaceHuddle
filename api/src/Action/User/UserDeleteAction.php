<?php

namespace App\Action\User;

use App\Data\AuthorisationData;
use App\Domain\User\Service\UserDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to deletes the currently logged-in user.
 *
 * @OA\Delete(
 *   path="/user/",
 *   summary="Delete the logged-in user.",
 *   tags={"User"},
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
final class UserDeleteAction
{
    use UserSelfActionTrait;
    protected UserDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserDeleter $service The service
     */
    public function __construct(Responder $responder, UserDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
