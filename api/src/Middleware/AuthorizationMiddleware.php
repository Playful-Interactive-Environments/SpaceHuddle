<?php

namespace App\Middleware;

use App\Data\AuthorisationData;
use App\Data\AuthorisationType;
use App\Domain\Infrastructure\Repository\InfrastructureRepository;
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
    private InfrastructureRepository $repository;

    /**
     * @param Enforcer $enforcer Casbin Enforcer class.
     * @param ResponseFactoryInterface $responseFactory The response factory.
     * @param InfrastructureRepository $repository The infrastructure repository
     */
    public function __construct(
        Enforcer $enforcer,
        ResponseFactoryInterface $responseFactory,
        InfrastructureRepository $repository
    ) {

        $this->enforcer = $enforcer;
        $this->enforcer->addFunction("getDefaultRoleFromType", function (string $type): string {
            return SessionRoleType::mapAuthorisationType($type);
        });
        $this->responseFactory = $responseFactory;
        $this->repository = $repository;
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

        $uri = $request->getUri();
        $action = $request->getMethod();
        // Get a URI/path without the basepath (now its /GAB/api/...)
        $uriPath = $this->removeBaseUrl($uri->getPath());
        if (!str_ends_with($uriPath, "/")) {
            $uriPath = $uriPath . "/";
        }

        if ($authorisation) {
            $authorisationType = strtoupper($authorisation->type);
            if ($this->enforcer->enforce($authorisationType, "ROUTE", $uriPath, $action)) {
                $parameter = $this->parseParameter($request);
                $role = strtoupper($this->getRole($authorisation, $parameter));
                if (!$this->enforcer->enforce($authorisationType, $role, $uriPath, $action)) {
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
     * Determine url and body parameters and associated entity.
     * @param ServerRequestInterface $request PSR-7 request
     * @return array Contained parameters
     */
    private function parseParameter(ServerRequestInterface $request): array
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $pattern = explode("/", str_replace("[/]", "/", $route->getPattern()));
        $pattern = array_filter($pattern, fn($value) => !is_null($value) && $value !== '');

        $dataEntity = [];
        $bodyData = [];
        if ($request->getMethod() == "PUT") {
            // Extract the form data from the request body
            $body = $request->getParsedBody() ?? [];
            if (array_key_exists("id")) {
                $bodyData["id"] = $body["id"];
                $dataEntity["id"] = end($pattern);
            }
        }

        $urlData = $route->getArguments();
        $data = array_merge($bodyData, $urlData);

        foreach (array_keys($urlData) as $key) {
            $paramIndex = array_search("{{$key}}", $pattern);
            if ($paramIndex > 0) {
                $dataEntity[$key] = $pattern[$paramIndex-1];
            }
        }

        return [
            "value" => $data,
            "entity" => $dataEntity
        ];
    }

    /**
     * Determines the session role
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $parameter Parameters and associated entities.
     * @return string Session role
     */
    protected function getRole(AuthorisationData $authorisation, array $parameter): string
    {
        $parameterValue = $parameter["value"];
        $parameterEntity = $parameter["entity"];
        $id = null;
        $entity = "user";

        if (key_exists("id", $parameterValue)) {
            $id = $parameterValue["id"];
            $entity = $parameterEntity["id"];
        } elseif (sizeof($parameterValue) > 0) {
            $urlParameterName = array_key_first($parameterValue);
            if (str_ends_with($urlParameterName, "Id")) {
                $id = $parameterValue[$urlParameterName];
                $entity = $parameterEntity[$urlParameterName];
            }
        }
        return $this->repository->getAuthorisationRole($authorisation, $entity, $id) ?? SessionRoleType::UNKNOWN;
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
