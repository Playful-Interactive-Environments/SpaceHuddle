<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionTemplateReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to read a list of all session templates.
 *
 * @OA\Get(
 *   path="/sessions/templates",
 *   summary="List of all session templates.",
 *   tags={"Session"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   @OA\Response(response="401", description="Unauthorized"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionTemplateReadAction
{
    use ActionTrait;
    protected SessionTemplateReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionTemplateReader $service The service
     */
    public function __construct(Responder $responder, SessionTemplateReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
