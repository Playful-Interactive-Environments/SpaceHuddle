<?php

namespace App\Action\User;

use App\Action\Base\ActionTrait;
use App\Data\AuthorisationData;

trait UserSelfActionTrait
{
    use ActionTrait;

    /**
     * Execute specific service functionality
     * @param AuthorisationData $authorisation Authorisation token data
     * @param array $bodyData Form data from the request body
     * @param array $urlData Url parameter from the request
     * @return mixed service result
     */
    protected function executeService(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ) : mixed {
        if ($authorisation->isUser()) {
            $urlData["id"] = $authorisation->id;
        }
        return $this->service->service($authorisation, $bodyData, $urlData);
    }
}
