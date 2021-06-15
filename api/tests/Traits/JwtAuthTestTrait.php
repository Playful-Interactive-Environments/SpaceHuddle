<?php

namespace App\Test\Traits;

use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP BasicAuth Test Trait.
 */
trait JwtAuthTestTrait
{
    /**
     * Determine json token
     * @param string $username Username of the authorised user
     * @param string $password Password of the authorised user
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

    /**
     * Add BasicAuth to request.
     *
     * @param ServerRequestInterface $request The request
     * @param string|null $token Use alternative Login
     *
     * @return ServerRequestInterface The request
     */
    protected function withJwtAuth(ServerRequestInterface $request, string $token = null): ServerRequestInterface
    {
        if (is_null($token)) {
            $token = $this->getAccessToken();
        }

        if (isset($token) and strlen($token) > 0) {
            return $request->withHeader("Authorization", "Bearer $token");
        } else {
            return $request;
        }
    }
}
