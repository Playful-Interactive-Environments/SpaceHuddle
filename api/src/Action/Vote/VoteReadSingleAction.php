<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteSingleReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the detail data for the vote with the specified id.
 *
 * @OA\Get(
 *   path="/vote/{id}/",
 *   summary="Get the task idea voting for the logged-in participant.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="id", description="ID of the voting", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/VoteData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteReadSingleAction
{
    use ActionTrait;
    protected VoteSingleReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteSingleReader $service The service
     */
    public function __construct(Responder $responder, VoteSingleReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
