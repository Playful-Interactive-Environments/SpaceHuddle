<?php


namespace App\Action\User;

use App\Domain\User\Service\UserLogin;
use App\Domain\User\Service\UserUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action to change the password for the currently logged in user.
 *
 * @OA\Put(
 *   path="/api/user/",
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
    private Responder $responder;

    private UserUpdater $userUpdater;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserUpdater $userUpdater The service
     */
    public function __construct(Responder $responder, UserUpdater $userUpdater)
    {
        $this->responder = $responder;
        $this->userUpdater = $userUpdater;
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

        $token = $request->getAttribute("token");
        $userId = $token["data"]->userId;

        // Invoke the Domain with inputs and retain the result
        $userResult = "Not implemented: update user $userId";

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $userResult)
            ->withStatus(StatusCodeInterface::STATUS_CREATED);
    }
}
