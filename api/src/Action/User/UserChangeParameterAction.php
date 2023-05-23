<?php


namespace App\Action\User;

use App\Domain\User\Service\UserUpdaterParameter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action to change the parameter for the currently logged in user.
 *
 * @OA\Put(
 *   path="/user/parameter/",
 *   summary="Change the parameter of the logged-in user.",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"parameter"},
 *         @OA\Property(property="parameter", type="object", format="json")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class UserChangeParameterAction
{
    use UserSelfActionTrait;
    protected UserUpdaterParameter $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserUpdaterParameter $service The service
     */
    public function __construct(Responder $responder, UserUpdaterParameter $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }
}
