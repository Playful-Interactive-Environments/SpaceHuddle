<?php

namespace App\Middleware;

use App\Data\AuthorisationData;
use App\Domain\Infrastructure\Repository\InfrastructureRepository;
use App\Domain\Session\Type\SessionRoleType;
use Casbin\Exceptions\CasbinException;
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

        // Extract the form data from the request body
        $bodyData = $request->getParsedBody();

        if ($authorisation) {
            $authorisationType = strtoupper($authorisation->type);
            $policy = $this->enforcer->enforceEx($authorisationType, $uriPath, $action);
            if ($policy && $policy[0]) {
                $policyPath = $policy[1][1];
                $parameter = $this->parseParameter($uriPath, $policyPath, $bodyData);
                $role = strtoupper($this->getRole($authorisation, $parameter));
                if (!$this->enforcer->enforce($role, $uriPath, $action)) {
                    return $this->responseFactory->createResponse()
                        ->withHeader("Content-Type", "application/json")
                        ->withStatus(
                            StatusCodeInterface::STATUS_FORBIDDEN,
                            "User has no rights for this entity."
                        );
                }
            } else {
                return $this->responseFactory->createResponse()
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(
                        StatusCodeInterface::STATUS_FORBIDDEN,
                        "User has no rights for this service."
                    );
            }
        }

        return $handler->handle($request);
    }

    /**
     * Determine url and body parameters and associated entity.
     * @param string $uriPath Request url
     * @param string $policyPath Connected polity url
     * @param array|null $bodyData Body parameter
     * @return array Contained parameters
     */
    private function parseParameter(string $uriPath, string $policyPath, ?array $bodyData): array
    {
        $urlData = [];
        $dataEntity = [];
        $policyPathParts = explode("/", $policyPath);
        $uriPathParts = explode("/", $uriPath);

        if ($bodyData) {
            $bodyKeys = array_keys($bodyData);
            foreach ($bodyKeys as $bodyKey) {
                $dataEntity[$bodyKey] = end($policyPathParts);
            }
        }

        for ($i = 0; $i < sizeof($policyPathParts); $i++) {
            $element = $policyPathParts[$i];
            if (str_starts_with($element, ":")) {
                $element = substr_replace($element, "", 0, 1);
                $urlData[$element] = $uriPathParts[$i];
                $dataEntity[$element] = $policyPathParts[$i-1];
            }
        }

        if ($bodyData) {
            $data = array_merge($bodyData, $urlData);

            return [
                "value" => $data,
                "entity" => $dataEntity
            ];
        }

        return [
            "value" => $urlData,
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
        if (key_exists("id", $parameterValue)) {
            $id = $parameterValue["id"];
            $entity = $parameterEntity["id"];
            return $this->repository->getAuthorisationRole($authorisation, $entity, $id);
        } elseif (sizeof($parameterValue) > 0) {
            $urlParameterName = array_key_first($parameterValue);
            if (str_ends_with($urlParameterName, "Id")) {
                $id = $parameterValue[$urlParameterName];
                $entity = $parameterEntity[$urlParameterName];
                return $this->repository->getAuthorisationRole($authorisation, $entity, $id);
            }
        }
        return SessionRoleType::UNKNOWN;
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
