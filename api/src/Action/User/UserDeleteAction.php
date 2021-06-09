<?php

namespace App\Action\User;

use App\Action\Base\AbstractAction;
use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Domain\User\Service\UserDeleter;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;

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
     * @param AuthorisationData $authorisation Authorisation token data
     * @param array $bodyData Form data from the request body
     * @param array $urlData Url parameter from the request
     * @return mixed service result
     * @throws AuthorisationException
     */
    protected function executeService(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ) : mixed {
        $userId = null;
        if ($authorisation->isUser()) {
            $userId = $authorisation->id;
        }
        return $this->service->service($authorisation, ["id" => $userId]);
    }
}
