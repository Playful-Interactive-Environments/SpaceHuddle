<?php

namespace App\Action\Session;

use App\Action\Base\ActionTrait;
use App\Domain\Session\Service\SessionExport;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for downloading a data export file.
 *
 * @OA\Get(
 *   path="/session/{id}/export/{exportType}/",
 *   summary="Download file for the session with the specified id.",
 *   tags={"Session"},
 *   @OA\Parameter(in="path", name="id", description="ID of the session to be exported", required=true),
 *   @OA\Parameter(in="path", name="exportType", description="export output format", required=true,
 *     @OA\Schema(ref="#/components/schemas/ExportType")),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ExportData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionExportAction
{
    use ActionTrait;
    protected SessionExport $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionExport $service The service
     */
    public function __construct(Responder $responder, SessionExport $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
