<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a session.
 * @OA\Put(
 *   path="/api/session/",
 *   summary="Update a session.",
 *   tags={"Session"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"id"},
 *         @OA\Property(property="id", type="string", example="uuid"),
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="maxParticipants", type="integer", example=100),
 *         @OA\Property(property="expirationDate", type="string", format="date"),
 *         @OA\Property(property="publicScreenModuleId", type="string", example=null)
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
class SessionUpdateAction
{
    use ActionTrait;
    protected SessionUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionUpdater $service The service
     */
    public function __construct(Responder $responder, SessionUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
