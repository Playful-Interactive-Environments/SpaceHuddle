<?php

namespace App\Action\User;

use App\Action\Base\ActionTrait;
use App\Domain\User\Service\UserConfirm;
use App\Domain\User\Service\UserConfirmOther;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for confirming a email of other user.
 *
 * @OA\Put(
 *   path="/user/{id}/confirm",
 *   summary="Confirm the email address of other user",
 *   tags={"User"},
 *   @OA\Parameter(
 *     in="path",
 *     name="id",
 *     description="confirm user id",
 *     required=true
 *     ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   @OA\Response(response="401", description="Unauthorized"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
final class UserConfirmOtherAction
{
    use ActionTrait;
    protected UserConfirmOther $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserConfirmOther $service The service
     */
    public function __construct(Responder $responder, UserConfirmOther $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
