<?php

namespace App\Test\Traits;

/**
 * Participant Test Trait.
 */
trait ParticipantTestTrait
{
    use UserAccessTokenTrait {
        UserAccessTokenTrait::getAccessToken as private getUserAccessToken;
    }
    use UserTestTrait {
        UserTestTrait::getFirstSession as private getFirstUserSession;
    }

    /**
     * Get the first session entry from the database
     * @return object|null First entry
     */
    private function getFirstSession() : ?object
    {
        return $this->getFirstUserSession($this->getUserAccessToken());
    }

    /**
     * Determine access data
     * @param string|null $sessionKey Session key of the connecting session
     * @param string $ip IP of the participant device
     * @return object|null json data
     */
    protected function getAccess(
        ?string $sessionKey = null,
        string $ip = "localhost"
    ): ?object {
        if (is_null($sessionKey)) {
            $sessionKey = $this->getFirstSessionKey();
        }

        $request = $this->createJsonRequest(
            "POST",
            "/participant/connect/",
            [
                "sessionKey" => $sessionKey,
                "ip" => $ip
            ]
        );
        $response = $this->app->handle($request);
        return json_decode($response->getBody());
    }

    /**
     * Determine json token
     * @param string|null $sessionKey Session key of the connecting session
     * @param string $ip IP of the participant device
     * @return string|null json token
     */
    protected function getAccessToken(
        ?string $sessionKey = null,
        string $ip = "localhost"
    ): ?string {
        $json = $this->getAccess($sessionKey, $ip);
        $accessToken = "";
        if (property_exists($json, "token")) {
            $accessToken = $json->token->accessToken;
        }
        return $accessToken;
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstBrowserKey() : ?string
    {
        $json = $this->getAccess();
        if (property_exists($json, "participant")) {
            return $json->participant->browserKey;
        }

        return null;
    }

    /**
     * Determine first topic id
     * @return string|null json token
     */
    protected function getFirstTopicId() : ?string
    {
        $result = $this->getFirstEntity(
            "participant/topics"
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstTaskId() : ?string
    {
        $result = $this->getFirstEntity(
            "participant/tasks"
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }
}
