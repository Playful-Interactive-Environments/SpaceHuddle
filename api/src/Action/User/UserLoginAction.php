<?php

namespace App\Action\User;

use App\Domain\User\Service\UserLogin;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 *
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
     * @OA\Post(
     *   path="/api/user/login/",
     *   summary="Perform a login with an existing user",
     *   tags={"User"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"username", "password"},
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="accessToken", type="string")
     *       )
     *     )),
     *   @OA\Response(response="404", description="Not Found")
     * )
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        // Invoke the Domain with inputs and retain the result
        $userId = $this->userLogin->loginUser($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, ['user_id' => $userId])
            ->withStatus(StatusCodeInterface::STATUS_CREATED);
    }
}
