<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaCreatorTask;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new idea for the specified task.
 *
 * @OA\Post(
 *   path="/api/task/{taskId}/idea/",
 *   summary="Create a new idea for the task.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"keywords"},
 *         @OA\Property(property="keywords", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="link", type="string"),
 *         @OA\Property(property="image", type="string", format="binary")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/IdeaData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaCreateForTaskAction
{
    use IdeaActionTrait;
    protected IdeaCreatorTask $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaCreatorTask $service The service
     */
    public function __construct(Responder $responder, IdeaCreatorTask $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }

}
