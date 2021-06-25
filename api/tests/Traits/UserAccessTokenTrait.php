<?php

namespace App\Test\Traits;

trait UserAccessTokenTrait
{
    /**
     * Determine json token
     * @param string $username Username of the authorised user
     * @param string $password Password of the authorised user
     * @param bool $createIfNotExists If true, a user is created with the specified data if this does not exist
     * @return string|null json token
     */
    protected function getAccessToken(
        string $username = "john.doe",
        string $password = "secret123",
        bool $createIfNotExists = true
    ): ?string {
        $request = $this->createJsonRequest(
            "POST",
            "/user/login/",
            [
                "username" => $username,
                "password" => $password
            ]
        );
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $accessToken = "";
        if (property_exists($json, "accessToken")) {
            $accessToken = $json->accessToken;
        } elseif ($createIfNotExists) {
            $request = $this->createJsonRequest(
                "POST",
                "/user/register/",
                [
                    "username" => $username,
                    "password" => $password,
                    "passwordConfirmation" => $password
                ]
            );
            $this->app->handle($request);
            $accessToken = $this->getAccessToken($username, $password, false);
        }
        return $accessToken;
    }
}
