<?php


namespace App\Action\Resource;

use App\Action\Base\ActionTrait;
use App\Domain\Resource\Service\ResourceCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new resource for the specified session.
 *
 * @OA\Post(
 *   path="/api/session/{sessionId}/resource/",
 *   summary="Create a new resource for the session.",
 *   tags={"Resource"},
 *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="link", type="string"),
 *         @OA\Property(property="image", type="string", format="binary")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/ResourceData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class ResourceCreateAction
{
    use ActionTrait;
    protected ResourceCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResourceCreator $service The service
     */
    public function __construct(Responder $responder, ResourceCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
