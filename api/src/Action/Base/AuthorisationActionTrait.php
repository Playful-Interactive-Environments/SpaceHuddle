<?php

namespace App\Action\Base;

use App\Data\AuthorisationData;

trait AuthorisationActionTrait
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
            $urlData["userId"] = $authorisation->id;
        } elseif ($authorisation->isParticipant()) {
            $urlData["participantId"] = $authorisation->id;
        }

        return $this->service->service($authorisation, $bodyData, $urlData);
    }
}
