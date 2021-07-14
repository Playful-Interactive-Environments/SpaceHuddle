<?php

namespace App\Action\Category;

use App\Action\Base\ActionTrait;
use App\Data\AuthorisationData;

trait CategoryIdeaActionTrait
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
        return $this->service->service($authorisation, ["ideas" => $bodyData], $urlData);
    }
}
