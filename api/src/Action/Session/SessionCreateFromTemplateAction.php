<?php


namespace App\Action\Session;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\Session\Service\SessionCreator;
use App\Domain\Session\Service\SessionCreatorFromTemplate;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for creating a session from a public template.
 *
 * @OA\Post(
 *   path="/session/{templateId}/template",
 *   summary="Create a session from a public template.",
 *   tags={"Session"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"title", "maxParticipants", "expirationDate"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="templateId", type="string"),
 *         @OA\Property(property="maxParticipants", type="integer", example=100),
 *         @OA\Property(property="expirationDate", type="string", format="date")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/SessionData"),
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class SessionCreateFromTemplateAction
{
    use AuthorisationActionTrait;
    protected SessionCreatorFromTemplate $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param SessionCreatorFromTemplate $service The service
     */
    public function __construct(Responder $responder, SessionCreatorFromTemplate $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
