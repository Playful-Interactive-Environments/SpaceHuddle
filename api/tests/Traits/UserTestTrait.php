<?php

namespace App\Test\Traits;

/**
 * User Test Trait.
 */
trait UserTestTrait
{
    private function getFirstSession(bool $createIfNotExists = true) : ?object
    {
        $request = $this->createJsonRequest(
            "GET",
            "/sessions/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $result = null;
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "id")) {
            $result = $json[0];
        } elseif ($createIfNotExists) {
            $request = $this->createJsonRequest(
                "POST",
                "/session/",
                [
                    "title" => "php unit test session",
                    "maxParticipants" => 100,
                    "expirationDate" => "2022-01-01"
                ]
            );
            $request = $this->withJwtAuth($request);
            $this->app->handle($request);
            $result = $this->getFirstSession(false);
        }
        return $result;
    }

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionId() : ?string
    {
        $result = $this->getFirstSession();
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionKey() : ?string
    {
        $result = $this->getFirstSession();
        if (is_object($result) and property_exists($result, "connectionKey")) {
            return $result->connectionKey;
        }
        return "";
    }
}
