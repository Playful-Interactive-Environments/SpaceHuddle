<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteHierarchyResultReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the result of the voting for the specified parent idea.
 *
 * @OA\Get(
 *   path="/hierarchy/{parentId}/vote_result/",
 *   summary="Returns the result of the voting for the specified parent idea.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="parentId", description="ID of the parent idea", required=true),
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
class VoteHierarchyResultReadAction
{
    use ActionTrait;
    protected VoteHierarchyResultReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteHierarchyResultReader $service The service
     */
    public function __construct(Responder $responder, VoteHierarchyResultReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
