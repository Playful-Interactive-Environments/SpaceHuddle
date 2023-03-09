<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionSubjectReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to read a list of all used subjects.
 *
 * @OA\Get(
 *   path="/sessions/subjects",
 *   summary="List of all used session subjects.",
 *   tags={"Session"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(type="string")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionSubjectReadAction
{
    use ActionTrait;
    protected SessionSubjectReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionSubjectReader $service The service
     */
    public function __construct(Responder $responder, SessionSubjectReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
