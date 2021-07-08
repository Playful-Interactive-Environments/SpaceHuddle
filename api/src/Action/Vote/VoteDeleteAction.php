<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a vote.
 *
 * @OA\Delete(
 *   path="/api/vote/{id}/",
 *   summary="Delete a voting.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="id", description="ID of voting to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteDeleteAction
{
    use ActionTrait;
    protected VoteDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteDeleter $service The service
     */
    public function __construct(Responder $responder, VoteDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
