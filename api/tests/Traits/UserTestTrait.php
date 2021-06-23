<?php

namespace App\Test\Traits;

/**
 * User Test Trait.
 */
trait UserTestTrait
{
    /**
     * Get first entity entry from the database
     * @param string $entityUrl Name of the get api call
     * @param array|null $postData If set, creates a new entry with the specified data if the result is empty
     * @return object|null First entry
     */
    private function getFirstEntity(string $entityUrl, ?array $postData = null) : ?object
    {
        $request = $this->createJsonRequest(
            "GET",
            "/$entityUrl/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $result = null;
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "id")) {
            $result = $json[0];
        } elseif (isset($postData)) {
            $entityNameSingular = substr_replace($entityUrl ,"", -1);
            $request = $this->createJsonRequest(
                "POST",
                "/$entityNameSingular/",
                $postData
            );
            $request = $this->withJwtAuth($request);
            $this->app->handle($request);
            $result = $this->getFirstEntity($entityUrl, null);
        }
        return $result;
    }

    /**
     * Get the first session entry from the database
     * @param bool $createIfNotExists If true, creates a new entry if the result is empty
     * @return object|null First entry
     */
    private function getFirstSession(bool $createIfNotExists = true) : ?object
    {
        return $this->getFirstEntity(
            "sessions",
            [
                "title" => "php unit test session",
                "description" => "create from unit test",
                "maxParticipants" => 100,
                "expirationDate" => "2022-01-01"
            ]
        );
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

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstTopicId() : ?string
    {
        $sessionId = $this->getFirstSessionId();
        $result = $this->getFirstEntity(
            "session/$sessionId/topics",
            [
                "title" => "php unit test topic",
                "description" => "create from unit test"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }
}
