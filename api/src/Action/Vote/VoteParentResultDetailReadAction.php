<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteParentResultDetailReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the parent result details of the voting for the specified parent idea.
 *
 * @OA\Get(
 *   path="/hierarchy/{taskId}/vote_result_parent/detail/",
 *   summary="Returns the result of the voting for the specified parent idea.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="parentId", description="ID of the parent idea", required=true),
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
class VoteParentResultDetailReadAction
{
    use ActionTrait;
    protected VoteParentResultDetailReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteParentResultDetailReader $service The service
     */
    public function __construct(Responder $responder, VoteParentResultDetailReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
