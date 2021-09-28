<?php

namespace App\Action\User;

use App\Action\Base\ActionTrait;
use App\Domain\User\Service\UserLogin;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for user login.
 *
 * @OA\Post(
 *   path="/user/login/",
 *   summary="Perform a login with an existing user",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"username", "password"},
 *         @OA\Property(property="username", type="string", example="john.doe@fhooe.at"),
 *         @OA\Property(property="password", type="string", example="Secret123!")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="201", description="Created.",
 *     @OA\JsonContent(ref="#/components/schemas/TokenData")),
 *   @OA\Response(response="400", ref="#/components/responses/400"),
 *   @OA\Response(response="403", ref="#/components/responses/403"),
 *   @OA\Response(response="422", ref="#/components/responses/422"),
 *   @OA\Response(response="500", ref="#/components/responses/500")
 * )
 */
final class UserLoginAction
{
    use ActionTrait;
    protected UserLogin $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserLogin $service The service
     */
    public function __construct(Responder $responder, UserLogin $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
