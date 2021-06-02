<?php

namespace App\Action\User;

use App\Domain\User\Service\UserLogin;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
 *           property="access_token",
 *           type="string",
 *           description="The access token.",
 *           example="reallylongtokenstring"
 *         ),
 *         @OA\Property(property="token_type", type="string", description="The token type.", example="Bearer"),
 *         @OA\Property(
 *           property="expires_in",
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
final class UserLoginAction
{
    private Responder $responder;

    private UserLogin $userLogin;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserLogin $userLogin The service
     */
    public function __construct(Responder $responder, UserLogin $userLogin)
    {
        $this->responder = $responder;
        $this->userLogin = $userLogin;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        // Invoke the Domain with inputs and retain the result
        $userResult = $this->userLogin->loginUser($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $userResult)
            ->withStatus(StatusCodeInterface::STATUS_CREATED);
    }
}
