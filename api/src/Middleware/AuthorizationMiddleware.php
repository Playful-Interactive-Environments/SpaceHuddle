<?php

namespace App\Middleware;

use App\Data\AuthorisationType;
use App\Domain\Infrastructure\Service\PermissionService;
use App\Domain\Session\Type\SessionRoleType;
use Casbin\Exceptions\CasbinException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Casbin\Enforcer;
use Slim\Routing\RouteContext;

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
     * @var PermissionService
     */
    private PermissionService $service;

    /**
     * @param Enforcer $enforcer Casbin Enforcer class.
     * @param ResponseFactoryInterface $responseFactory The response factory.
     * @param PermissionService $service The permission service
     */
    public function __construct(
        Enforcer $enforcer,
        ResponseFactoryInterface $responseFactory,
        PermissionService $service
    ) {

        $this->enforcer = $enforcer;
        $this->responseFactory = $responseFactory;
        $this->service = $service;
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
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authorisation = $request->getAttribute('authorisation');
        $bodyData = $request->getParsedBody();

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $urlData = $route->getArguments();
        $urlPattern = $route->getPattern();

        $uri = $request->getUri();
        $action = $request->getMethod();
        // Get a URI/path without the basepath (now its /GAB/api/...)
        $uriPath = $this->removeBaseUrl($uri->getPath());
        if (!str_ends_with($uriPath, "/")) {
            $uriPath = $uriPath . "/";
        }

        if ($authorisation) {
            $authorisationType = strtoupper($authorisation->type);
            if ($this->enforcer->enforce(
                $authorisationType,
                SessionRoleType::mapAuthorisationType($authorisationType),
                $uriPath,
                $action
            )) {
                $sessionRole = $this->service->service(
                    $authorisation,
                    $bodyData,
                    $urlData,
                    $urlPattern,
                    $action,
                    false
                );
                if (!$this->enforcer->enforce($authorisationType, $sessionRole, $uriPath, $action)) {
                    return $this->responseFactory->createResponse()
                        ->withHeader("Content-Type", "application/json")
                        ->withStatus(
                            StatusCodeInterface::STATUS_FORBIDDEN,
                            "User has no rights for this entity."
                        );
                }
            } elseif (strtoupper($authorisation->type) != strtoupper(AuthorisationType::NONE)) {
                return $this->responseFactory->createResponse()
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(
                        StatusCodeInterface::STATUS_FORBIDDEN,
                        "User has no rights for this service."
                    );
            } else {
                return $this->responseFactory->createResponse()
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(
                        StatusCodeInterface::STATUS_UNAUTHORIZED,
                        "No valid access token specified."
                    );
            }
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
