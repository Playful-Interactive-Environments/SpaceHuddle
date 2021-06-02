<?php

namespace App\Middleware;

use App\Routing\JwtAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JWT Claim middleware.
 */
final class JwtClaimMiddleware implements MiddlewareInterface
{
    /**
     * @var JwtAuth
     */
    private JwtAuth $jwtAuth;

    /**
     * The constructor.
     *
     * @param JwtAuth $jwtAuth The JWT auth
     */
    public function __construct(JwtAuth $jwtAuth)
    {
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authorization = explode(' ', (string)$request->getHeaderLine('Authorization'));
        $type = $authorization[0] ?? '';
        $credentials = $authorization[1] ?? '';
        if ($type !== 'Bearer') {
            return $handler->handle($request);
        }
        $token = $this->jwtAuth->validateToken($credentials);
        if ($token) {
            // Append valid token
            $request = $request->withAttribute('token', $token);
            // Append the user id as request attribute
            $request = $request->withAttribute('userId', $token->claims()->get('userId'));
            // Append the username as request attribute
            $request = $request->withAttribute('username', $token->claims()->get('username'));
            // Add more claim values as attribute...
            //$request = $request->withAttribute('locale', $token->claims()->get('locale'));
        }
        return $handler->handle($request);
    }
}
