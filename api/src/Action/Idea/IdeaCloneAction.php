<?php

namespace App\Action\Idea;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Idea\Service\IdeaCloner;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for cloning an idea.
 *
 * @OA\Post(
 *   path="/idea/{id}/clone",
 *   summary="Clones a idea.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="id", description="ID of the idea to be cloned", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/IdeaData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaCloneAction
{
    use AuthorisationActionTrait;
    protected IdeaCloner $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaCloner $service The service
     */
    public function __construct(Responder $responder, IdeaCloner $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
