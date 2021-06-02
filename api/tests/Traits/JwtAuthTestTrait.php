<?php

namespace App\Test\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Tuupola\Middleware\JwtAuthentication;

/**
 * HTTP BasicAuth Test Trait.
 */
trait JwtAuthTestTrait
{
    /**
     * Add BasicAuth to request.
     *
     * @param ServerRequestInterface $request The request
     *
     * @return ServerRequestInterface The request
     */
    protected function withJwtAuth(ServerRequestInterface $request): ServerRequestInterface
    {
        function apiSettings() : array {
            return require __DIR__ . "/../../config/settings.php";
        }

        $settings = apiSettings()["jwt_auth"];

        $this->setContainerValue(JwtAuthentication::class, new JwtAuthentication($settings));

        return $request->withHeader("Authorization", "Bearer " . base64_encode("api-user:secret"));
    }
}
