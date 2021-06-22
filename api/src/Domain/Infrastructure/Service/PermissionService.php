<?php

namespace App\Domain\Infrastructure\Service;

use App\Data\AuthorisationData;
use App\Domain\Infrastructure\Repository\InfrastructureRepository;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Service that checks the session role for the current route.
 * @package App\Domain\Infrastructure\Service
 */
class PermissionService
{
    protected InfrastructureRepository $repository;

    /**
     * The constructor.
     *
     * @param InfrastructureRepository $repository The repository
     */
    public function __construct(
        InfrastructureRepository $repository,
    ) {
        $this->repository = $repository;
    }

    /**
     * Functionality of the permission service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $requestBody Form data from the request body
     * @param array<string, string> $urlData Url parameter from the request
     * @param string $routePattern Route pattern
     * @param string $routeMethod Route pattern
     * @param bool $readOnly If true, get authorisation read role. If false, get authorisation role.
     *
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function service(
        AuthorisationData $authorisation,
        array|object|null $requestBody,
        array $urlData,
        string $routePattern,
        string $routeMethod,
        bool $readOnly = false
    ): ?string {
        //Determine url and body parameters and associated entity.

        $patternParts = explode("/", str_replace("[/]", "/", $routePattern));
        $patternParts = array_filter($patternParts, fn($value) => !is_null($value) && $value !== '');

        $dataEntity = [];
        $bodyData = [];
        if ($routeMethod == "PUT") {
            // Extract the form data from the request body
            $body = (array)$requestBody ?? [];
            if (array_key_exists("id", $body)) {
                $bodyData["id"] = $body["id"];
                if (end($patternParts)) {
                    $dataEntity["id"] = (string)end($patternParts);
                }
            }
        }

        $data = array_merge($bodyData, $urlData);

        foreach (array_keys($urlData) as $key) {
            $paramIndex = array_search("{{$key}}", $patternParts);
            if ($paramIndex > 0) {
                $dataEntity[$key] = $patternParts[$paramIndex-1];
            }
        }

        //Determines the session role
        return $this->getRole($authorisation, $data, $dataEntity, $readOnly);
    }

    /**
     * Determines the session role
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, string> $parameterValue Request parameters.
     * @param array<string, string> $parameterEntity Associated entities.
     * @param bool $readOnly If true, get authorisation read role. If false, get authorisation role.
     * @return string Session role
     */
    protected function getRole(
        AuthorisationData $authorisation,
        array $parameterValue,
        array $parameterEntity,
        bool $readOnly = false
    ): string {
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

        if ($readOnly) {
            return $this->repository->getAuthorisationReadRole($authorisation, $entity, $id) ??
                SessionRoleType::UNKNOWN;
        }

        return $this->repository->getAuthorisationRole($authorisation, $entity, $id) ?? SessionRoleType::UNKNOWN;
    }
}
