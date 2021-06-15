<?php

namespace App\Test\Traits;

use Selective\TestTrait\Traits\HttpJsonTestTrait;

/**
 * User Test Trait.
 */
trait UserTestTrait
{
    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionId() : ?string
    {
        $request = $this->createJsonRequest(
            "GET",
            "/sessions/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $id = "";
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "id")) {
            $id = $json[0]->id;
        }
        return $id;
    }

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionKey() : ?string
    {
        $request = $this->createJsonRequest(
            "GET",
            "/sessions/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $key = "";
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "connectionKey")) {
            $key = $json[0]->connectionKey;
        }
        return $key;
    }
}
