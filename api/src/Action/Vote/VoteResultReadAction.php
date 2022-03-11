<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteResultReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the parent result of the voting for the specified task.
 *
 * @OA\Get(
 *   path="/task/{taskId}/vote_result_parent/",
 *   summary="Returns the parent result of the voting for the specified task.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
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
class VoteResultReadAction
{
    use ActionTrait;
    protected VoteResultReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteResultReader $service The service
     */
    public function __construct(Responder $responder, VoteResultReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
