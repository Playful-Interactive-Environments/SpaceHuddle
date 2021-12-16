<?php

namespace App\Action\Tutorial;

use App\Action\Base\ActionTrait;
use App\Domain\Tutorial\Service\TutorialReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all read tutorial steps.
 *
 * @OA\Get(
 *   path="/tutorial_steps/",
 *   summary="List of all read tutorial steps for the active user.",
 *   tags={"Tutorial"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/TutorialData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class TutorialReadAllAction
{
    use ActionTrait;
    protected TutorialReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param TutorialReader $service The service
     */
    public function __construct(Responder $responder, TutorialReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }
}
