<?php

namespace App\Action\Idea;

use App\Action\Base\ActionTrait;
use App\Domain\Idea\Service\IdeaImageReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading the image data for the idea with the specified id.
 *
 * @OA\Get(
 *   path="/idea/{id}/image/",
 *   summary="Image data for the idea with the specified id.",
 *   tags={"Idea"},
 *   @OA\Parameter(in="path", name="id", description="ID of idea to return", required=true),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ImageData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class IdeaReadImageAction
{
    use ActionTrait;
    protected IdeaImageReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param IdeaImageReader $service The service
     */
    public function __construct(Responder $responder, IdeaImageReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
