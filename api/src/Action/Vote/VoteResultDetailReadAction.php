<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteResultDetailReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the result details of the voting for the specified task.
 *
 * @OA\Get(
 *   path="/task/{taskId}/vote_result/detail/",
 *   summary="Returns the parent result of the voting for the specified task.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/VoteResultDetailData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteResultDetailReadAction
{
    use ActionTrait;
    protected VoteResultDetailReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteResultDetailReader $service The service
     */
    public function __construct(Responder $responder, VoteResultDetailReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
