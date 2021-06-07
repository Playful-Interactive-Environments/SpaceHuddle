<?php

namespace App\Action\User;

use App\Action\Base\AbstractAction;
use App\Domain\User\Service\UserDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action to deletes the currently logged-in user.
 *
 * @OA\Delete(
 *   path="/api/user/",
 *   summary="Delete the logged-in user.",
 *   tags={"User"},
 *   @OA\Response(response="200", description="Success"),
 *   @OA\Response(response="404", description="Not Found"),
 *   security={{"api_key": {}}, {"bearerAuth": {}}}
 * )
 */
final class UserDeleteAction extends AbstractAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param UserDeleter $service The service
     */
    public function __construct(Responder $responder, UserDeleter $service)
    {
        parent::__construct($responder, $service);
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
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
        return $this->service->service($userId);
    }
}
