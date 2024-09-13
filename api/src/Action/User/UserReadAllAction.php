<?php

namespace App\Action\User;

use App\Action\Base\AuthorisationActionTrait;
use App\Domain\User\Service\UserReader;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Action for reading a list of all users.
 *
 * @OA\Get(
 *   path="/users/",
 *   summary="List of all users.",
 *   tags={"User"},
 *   @OA\Response(response="200", description="Success",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/UserAdminData")),
 *     )
 *   ),
 *   @OA\Response(response="404", description="Not Found"),
 *   @OA\Response(response="401", description="Unauthorized"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
final class UserReadAllAction
{
    use AuthorisationActionTrait;
    protected UserReader $service;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserReader $service The service
     */
    public function __construct(Responder $responder, UserReader $service)
    {
        $this->setUp($responder);
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_ACCEPTED;
    }
}
