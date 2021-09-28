<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for deleting a idea.
 *
 * @OA\Delete(
 *   path="/idea/{id}/",
 *   summary="Delete an idea.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="id", description="ID of idea to delete", required=true),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaDeleteAction
{
    use ActionTrait;
    protected IdeaDeleter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaDeleter $service The service
     */
    public function __construct(Responder $responder, IdeaDeleter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
