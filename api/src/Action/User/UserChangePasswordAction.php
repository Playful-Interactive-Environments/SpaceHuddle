<?php


namespace App\Action\User;

use App\Action\Base\AbstractAction;
use App\Domain\User\Service\UserUpdater;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action to change the password for the currently logged in user.
 *
 * @OA\Put(
 *   path="/api/user/",
 *   summary="Change the password of the logged-in user.",
 *   tags={"User"},
 *   @OA\RequestBody(
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(required={"oldPassword", "password", "passwordConfirmation"},
 *         @OA\Property(property="oldPassword", type="string"),
 *         @OA\Property(property="password", type="string"),
 *         @OA\Property(property="passwordConfirmation", type="string")
 *       )
 *     )
 *   ),
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
class UserChangePasswordAction extends AbstractAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserUpdater $service The service
     */
    public function __construct(Responder $responder, UserUpdater $service)
    {
        parent::__construct($responder, $service);
        $this->successStatusCode = StatusCodeInterface::STATUS_CREATED;
    }

    /**
     * Execute specific service functionality
     * @param ServerRequestInterface $request The request
     * @param array $data form data from the request body
     * @return mixed service result
     */
    protected function executeService(ServerRequestInterface $request, array $data) : mixed {

        $userId = $request->getAttribute("userId");

        // Invoke the Domain with inputs and retain the result
        return $this->service->servicePassword($userId, $data);
    }
}
