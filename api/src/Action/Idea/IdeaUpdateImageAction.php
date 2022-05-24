<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaImageUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for updating a image for a idea.
 *
 * @OA\Put(
 *   path="/idea/image/",
 *   summary="Update a image for a idea.",
 *   tags={"Idea"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/ImageData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaUpdateImageAction
{
    use ActionTrait;
    protected IdeaImageUpdater $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaImageUpdater $service The service
     */
    public function __construct(Responder $responder, IdeaImageUpdater $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
