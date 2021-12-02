<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteHierarchyReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the votes for the specified hierarchy idea.
 *
 * @OA\Get(
 *   path="/hierarchy/{parentId}/votes/",
 *   summary="Get all hierarchy idea votings for the logged-in participant.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="parentId", description="ID of the hierarchy idea", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/VoteData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteHierarchyReadAllAction
{
    use ActionTrait;
    protected VoteHierarchyReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteHierarchyReader $service The service
     */
    public function __construct(Responder $responder, VoteHierarchyReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
