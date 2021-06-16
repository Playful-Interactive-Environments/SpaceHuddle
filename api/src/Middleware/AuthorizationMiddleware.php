<?php

namespace App\Middleware;

use Casbin\Model\Model;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Casbin\Enforcer;

/**
 * Authorization middleware using Casbin.
 */
class AuthorizationMiddleware
{
    /**
     * @var Enforcer
     */
    private Enforcer $enforcer;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @param Enforcer $enforcer Casbin Enforcer class.
     * @param ResponseFactoryInterface $responseFactory The response factory.
     */
    public function __construct(Enforcer $enforcer, ResponseFactoryInterface $responseFactory)
    {
        $this->enforcer = $enforcer;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Authorization middleware invokable class.
     *
     * @param ServerRequestInterface $request PSR-7 request
     * @param RequestHandlerInterface $handler PSR-15 request handler
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // TODO: Get our user ID here
        $user = $request->getAttribute('user');
        // TODO: Get a URI/path without the basepath (now its /GAB/api/...)
        $uri = $request->getUri();
        $action = $request->getMethod();

        if ($user && !$this->enforcer->enforce($user, $uri->getPath(), $action)) {
            return $this->responseFactory->createResponse()
                ->withHeader("Content-Type", "application/json")
                ->withStatus(StatusCodeInterface::STATUS_FORBIDDEN, "Unauthorized.");
        }

        return $handler->handle($request);
    }
}
