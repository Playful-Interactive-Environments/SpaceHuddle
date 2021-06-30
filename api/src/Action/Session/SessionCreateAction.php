<?php


namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new session.
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
 *         @OA\Property(property="description", type="string"),
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
    use SessionActionTrait;
    protected SessionCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionCreator $service The service
     */
    public function __construct(Responder $responder, SessionCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
