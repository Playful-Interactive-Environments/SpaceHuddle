<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Data\AuthorisationData;
use App\Domain\Session\Service\SessionInfoReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the sessions for which the user is authorized.
 *
 * @OA\POST(
 *   path="/session_infos/",
 *   summary="List of all session infos for the connection keys.",
 *   tags={"Session"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(type="array",
 *         @OA\Items( type="string", example="connection_key")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionInfo")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found")
 * )
 */

class SessionReadInfosAction
{
    use ActionTrait;
    protected SessionInfoReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionInfoReader $service The service
     */
    public function __construct(Responder $responder, SessionInfoReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }

    /**
     * Execute specific service functionality
     * @param AuthorisationData $authorisation Authorisation token data
     * @param array $bodyData Form data from the request body
     * @param array $urlData Url parameter from the request
     * @return mixed service result
     */
    protected function executeService(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ) : mixed {
        return $this->service->service($authorisation, ["keys" => $bodyData], $urlData);
    }
}
