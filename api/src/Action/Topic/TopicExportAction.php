<?php

namespace App\Action\Topic;

use App\Action\Base\ActionTrait;
use App\Domain\Topic\Service\TopicExport;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for downloading a data export file.
 *
 * @OA\Get(
 *   path="/topic/{id}/export/{exportType}/",
 *   summary="Download file for the topic with the specified id.",
 *   tags={"Topic"},
 *   @OA\Parameter(in="path", name="id", description="ID of the topic to be exported", required=true),
 *   @OA\Parameter(in="path", name="exportType", description="export output format", required=true,
 *     @OA\Schema(ref="#/components/schemas/ExportType")),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ExportData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TopicExportAction
{
    use ActionTrait;
    protected TopicExport $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TopicExport $service The service
     */
    public function __construct(Responder $responder, TopicExport $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
