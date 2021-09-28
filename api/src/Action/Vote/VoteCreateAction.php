<?php

namespace App\Action\Vote;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Vote\Service\VoteCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new vote for the specified task.
 *
 * @OA\Post(
 *   path="/task/{taskId}/vote/",
 *   summary="Create a new voting for the task by the logged-in participant.",
 *   tags={"Vote"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"ideaId", "rating"},
 *         @OA\Property(property="ideaId", type="string"),
 *         @OA\Property(property="rating", type="integer", format="int"),
 *         @OA\Property(property="detailRating", type="number", format="float")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/VoteData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class VoteCreateAction
{
    use AuthorisationActionTrait;
    protected VoteCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param VoteCreator $service The service
     */
    public function __construct(Responder $responder, VoteCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
