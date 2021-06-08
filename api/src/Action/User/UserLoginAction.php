<?php

namespace App\Action\User;

use App\Action\Base\AbstractAction;
use App\Domain\User\Service\UserLogin;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for user login.
 *
 * @OA\Post(
 *   path="/api/user/login/",
 *   summary="Perform a login with an existing user",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"username", "password"},
 *         @OA\Property(property="username", type="string", example="john.doe"),
 *         @OA\Property(property="password", type="string", example="secret123")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="201", description="Created.",
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         @OA\Property(property="message", type="string", description="Success message.", example="Successful login."),
 *         @OA\Property(
 *           property="accessToken",
 *           type="string",
 *           description="The access token.",
 *           example="reallylongtokenstring"
 *         ),
 *         @OA\Property(property="tokenType", type="string", description="The token type.", example="Bearer"),
 *         @OA\Property(
 *           property="expiresIn",
 *           type="int",
 *           description="The token expiration in seconds.",
 *           example="86400"
 *         ),
 *       )
 *     )),
 *   @OA\Response(response="400", ref="#/components/responses/400"),
 *   @OA\Response(response="403", ref="#/components/responses/403"),
 *   @OA\Response(response="422", ref="#/components/responses/422"),
 *   @OA\Response(response="500", ref="#/components/responses/500")
 * )
 */
final class UserLoginAction extends AbstractAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserLogin $service The service
     */
    public function __construct(Responder $responder, UserLogin $service)
    {
        parent::__construct($responder, $service);
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
