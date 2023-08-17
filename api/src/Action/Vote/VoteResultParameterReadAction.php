<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteResultParameterReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the result of the voting parameter for the specified task.
 *
 * @OA\Get(
 *   path="/task/{taskId}/vote_result_parameter/{parameter}/",
 *   summary="Returns the parameter result of the voting for the specified task.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Parameter(in="path", name="parameter", description="parameter name", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/VoteResultData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteResultParameterReadAction
{
    use ActionTrait;
    protected VoteResultParameterReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteResultParameterReader $service The service
     */
    public function __construct(Responder $responder, VoteResultParameterReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
