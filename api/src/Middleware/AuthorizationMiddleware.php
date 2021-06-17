<?php

namespace App\Middleware;

use Casbin\Exceptions\CasbinException;
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
     * @throws CasbinException
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorisation = $request->getAttribute('authorisation');
        // TODO: Get session role from repository (getAuthorisationRole)
        $role = "moderator";

        $uri = $request->getUri();
        $action = $request->getMethod();
        // Get a URI/path without the basepath (now its /GAB/api/...)
        $uriPath = $this->removeBaseUrl($uri->getPath());

        if ($authorisation && !$this->enforcer->enforce($authorisation->type, $uriPath, $action)) {
            return $this->responseFactory->createResponse()
                ->withHeader("Content-Type", "application/json")
                ->withStatus(StatusCodeInterface::STATUS_FORBIDDEN, "User has no rights for this service or entity.");
        }

        return $handler->handle($request);
    }

    /**
     * Get a URI/path without the base path (now its /GAB/api/...)
     * @param string $uriPath Uri with base path
     * @return string Uri without base path
     */
    private function removeBaseUrl(string $uriPath): string
    {
        $indexPath = "/public/index.php";
        $scriptName = $_SERVER['SCRIPT_NAME'];

        // base url
        $baseUrl = str_replace($indexPath, "", $scriptName);
        return str_replace($baseUrl, "", $uriPath);
    }
}
