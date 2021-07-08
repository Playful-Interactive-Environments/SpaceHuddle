<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a vote.
 *
 * @OA\Put(
 *   path="/api/vote/",
 *   summary="Update a vote for the task by the logged-in participant.",
 *   tags={"Vote"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/VoteData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/VoteData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteUpdateAction
{
    use ActionTrait;
    protected VoteUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteUpdater $service The service
     */
    public function __construct(Responder $responder, VoteUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
