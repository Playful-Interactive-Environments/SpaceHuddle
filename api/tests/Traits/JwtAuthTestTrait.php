<?php

namespace App\Test\Traits;

use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP BasicAuth Test Trait.
 */
trait JwtAuthTestTrait
{
    use UserAccessTokenTrait;

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
