<?php


namespace App\Action\Tutorial;

use App\Action\Base\ActionTrait;
use App\Domain\Tutorial\Service\TutorialCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a new tutorial for the specified topic.
 *
 * @OA\Post(
 *   path="/tutorial_step/",
 *   summary="Add a new read tutral step to the user list.",
 *   tags={"Tutorial"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/TutorialData")
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/TutorialData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TutorialCreateAction
{
    use ActionTrait;
    protected TutorialCreator $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TutorialCreator $service The service
     */
    public function __construct(Responder $responder, TutorialCreator $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
