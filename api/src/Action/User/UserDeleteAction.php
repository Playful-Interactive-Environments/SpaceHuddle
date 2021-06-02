<?php

namespace App\Action\User;

use App\Domain\User\Service\UserDeleter;
use App\Responder\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action to deletes the currently logged-in user.
 *
 * @OA\Delete(
 *   path="/api/user/",
 *   summary="Delete the logged-in user.",
 *   tags={"User"},
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
final class UserDeleteAction
{
    private UserDeleter $userDeleter;

    private Responder $responder;

    /**
     * The constructor.
     *
     * @param UserDeleter $userDeleter The service
     * @param Responder $responder The responder
     */
    public function __construct(UserDeleter $userDeleter, Responder $responder)
    {
        $this->userDeleter = $userDeleter;
        $this->responder = $responder;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param array<mixed> $args The routing arguments
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        $userId = $request->getAttribute("userId");

        // Invoke the Domain with inputs and retain the result
        $userResult = "Not implemented: delete user $userId";

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $userResult);

        /*
        // Fetch parameters from the request
        $userId = (int)$args['user_id'];

        // Invoke the domain (service class)
        $this->userDeleter->deleteUser($userId);

        // Render the json response
        return $this->responder->withJson($response);*/
    }
}
