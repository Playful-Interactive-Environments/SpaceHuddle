<?php


namespace App\Action\Session;

use App\Domain\Session\Service\SessionCreate;
use App\Domain\User\Service\UserLogin;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action Create a new session.
 *
 * @OA\Post(
 *   path="/api/session/",
 *   summary="Create a new session.",
 *   tags={"Session"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title", "maxParticipants", "expirationDate"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="maxParticipants", type="integer", example=100),
 *         @OA\Property(property="expirationDate", type="string", format="date")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionCreateAction
{
    private Responder $responder;

    private SessionCreate $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionCreate $service The service
     */
    public function __construct(Responder $responder, SessionCreate $service)
    {
        $this->responder = $responder;
        $this->service = $service;
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

        $userId = $request->getAttribute("userId");

        // Invoke the Domain with inputs and retain the result
        $userResult = $this->service->createSession($userId, $data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $userResult)
            ->withStatus(StatusCodeInterface::STATUS_CREATED);
    }
}
