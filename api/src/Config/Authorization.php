<?php

namespace PieLab\GAB\Config;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Firebase\JWT\JWT;

/**
 * Central class to manage authorization. This class creates bearer tokens to authenticate users with the system.
 * @package PieLab\GAB\Config
 */
class Authorization
{
    /**
     * Token key.
     * @var string
     */
    private const KEY = "gab";

    /**
     * Token algorithm used by JWT.
     * @var string
     */
    private const ALGORITHM = "HS256";

    /**
     * Generates a bearer token.
     * @param array $data Usually a combination of login ID and username.
     * @return string The bearer token.
     */
    public static function generateToken(array $data): string
    {
        $settings = Settings::getInstance();
        $timeZone = $settings->get("timezone");
        $tokenSettings = $settings->get("token");
        try {
            $issuedAt = new DateTimeImmutable("now", new DateTimeZone($timeZone));
            $expiresAt = $issuedAt->add(new DateInterval($tokenSettings["expiresAfter"]));
        } catch (Exception $e) {
            http_response_code(500);
            $error = json_encode(
                [
                    "state" => "Server error",
                    "message" => $e->getMessage()
                ]
            );
            die($error);
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
            || $_SERVER["SERVER_PORT"] == 443) ? "https://" : "http://";
        $issuer = $protocol . $_SERVER["HTTP_HOST"] . "/api/";

        $token = [
            "iat" => $issuedAt->getTimestamp(),
            "exp" => $expiresAt->getTimestamp(),
            "iss" => $issuer,
            "data" => $data
        ];

        return JWT::encode($token, self::KEY, self::ALGORITHM);
    }

    /**
     * Get the authorization header.
     * @return string|null Returns the header or null if not available.
     */
    private static function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER["Authorization"])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists("apache_request_headers")) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions
            // (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(
                array_map("ucwords", array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            //print_r($requestHeaders);
            if (isset($requestHeaders["Authorization"])) {
                $headers = trim($requestHeaders["Authorization"]);
            }
        }

        return $headers;
    }

    /**
     * Get the access token from the headers.
     * @return string|null Returns the token or null.
     */
    private static function getBearerToken(): ?string
    {
        $headers = self::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match("/Bearer\s(\S+)/", $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Returns the authorization data.
     * @return object|null The payload as a PHP object or an error message.
     */
    private static function getAuthorizationData(): ?object
    {
        $jwt = self::getBearerToken();
        $errorMessage = "No token specified.";
        if (isset($jwt)) {
            $errorMessage = "Token expired.";
            try {
                $decoded = JWT::decode($jwt, self::KEY, [self::ALGORITHM]);
                $now = new DateTimeImmutable("now", new DateTimeZone(Settings::getInstance()->get("timezone")));
                if ($decoded->exp > $now->getTimestamp()) {
                    return $decoded->data;
                }
            } catch (Exception) {
                $errorMessage = "Wrong token.";
            }
        }
        http_response_code(401);
        $error = json_encode(
            [
                "state" => "Unauthorized",
                "message" => $errorMessage
            ]
        );
        die($error);
    }

    /**
     * Retrieves a single property from the authorization data.
     *
     * @param string $property The property to retrieve.
     * @return string The property's value.
     */
    public static function getAuthorizationProperty(string $property): string
    {
        $authorizationData = self::getAuthorizationData();
        if (property_exists($authorizationData, $property)) {
            return $authorizationData->$property;
        }
        http_response_code(401);
        $error = json_encode(
            [
                "state" => "Wrong Authorization Type",
                "message" => "Property '" . $property . "' does not exist in authorization header."
            ]
        );
        die($error);
    }

    /**
     * Checks if the current entity is a participant or not.
     * @return bool Returns true if it is a participant, otherwise false.
     */
    public static function isParticipant(): bool
    {
        $authorizationData = self::getAuthorizationData();
        if (property_exists($authorizationData, "participantId")) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the current entity is a user or not.
     * @return bool Returns true if it is a user, otherwise false.
     */
    public static function isUser(): bool
    {
        $authorization_data = self::getAuthorizationData();
        if (property_exists($authorization_data, "loginId")) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the current entity is logged in or not.
     * @return bool Returns true if logged in, otherwise false.
     */
    public static function isLoggedIn(): bool
    {
        $jwt = self::getBearerToken();
        if (isset($jwt)) {
            try {
                $decoded = JWT::decode($jwt, self::KEY, [self::ALGORITHM]);
                if ($decoded->exp > time()) {
                    return true;
                }
            } catch (Exception) {
            }
        }
        return false;
    }
}
