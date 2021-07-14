<?php

namespace App\Action\Vote;

use App\Action\Base\ActionTrait;
use App\Domain\Vote\Service\VoteReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all the votes for the specified topic.
 *
 * @OA\Get(
 *   path="/api/task/{taskId}/votes/",
 *   summary="Get all task votings for the logged-in participant.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
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
class VoteReadAllAction
{
    use ActionTrait;
    protected VoteReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteReader $service The service
     */
    public function __construct(Responder $responder, VoteReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
