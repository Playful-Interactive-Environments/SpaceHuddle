<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

// show error reporting
error_reporting(E_ALL);

// set your default time-zone
date_default_timezone_set('Europe/Vienna');

// variables used for jwt
$key = "gab";
$issued_at = time();
$expiration_time = $issued_at + (60 * 60 * 24); // valid for 24 hour
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$issuer = $protocol . $_SERVER['HTTP_HOST'] . "/api/";
$alg = 'HS256';

/**
 * Generates a bearer token.
 * @param array $data Usually a combination of login ID and username.
 * @return string The bearer token.
 */
function generateToken(array $data): string
{
    global $issued_at, $expiration_time, $issuer, $key, $alg;
    $token = [
        "iat" => $issued_at,
        "exp" => $expiration_time,
        "iss" => $issuer,
        "data" => $data
    ];
    // generate jwt
    return JWT::encode($token, $key, $alg);
}

/**
 * Get the authorization header.
 *
 * @return string|null Returns the header or null if not available.
 */
function getAuthorizationHeader(): ?string
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
    }
    return $headers;
}

/**
 * Get the access token from the headers.
 *
 * @return string|null Returns the token or null.
 */
function getBearerToken(): ?string
{
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Returns the authorization data.
 *
 * @return object The payload as a PHP object.
 */
function getAuthorizationData(): object
{
    global $key, $alg;
    $jwt = getBearerToken();
    $error_msg = "no token specified";
    if (isset($jwt)) {
        $error_msg = "token expired";
        try {
            $decoded = JWT::decode($jwt, $key, array($alg));
            if ($decoded->exp > time()) {
                return $decoded->data;
            }
        } catch (Exception $e) {
            $error_msg = "wrong token";
        }
    }
    http_response_code(401);
    $error = json_encode(
        [
            "state" => "Unauthorized",
            "message" => $error_msg
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
function getAuthorizationProperty(string $property): string
{
    $authorization_data = getAuthorizationData();
    if (property_exists($authorization_data, $property)) {
        return $authorization_data->$property;
    }
    http_response_code(401);
    $error = json_encode(
        array(
            "state" => "Wrong Authorization Type",
            "message" => "Property '" . $property . "' not exists in authorization header"
        )
    );
    die($error);
}

/**
 * Checks if the current entity is a participant or not.
 *
 * @return bool Returns true if it is a participant, otherwise false.
 */
function isParticipant(): bool
{
    $authorization_data = getAuthorizationData();
    if (property_exists($authorization_data, "participant_id")) {
        return true;
    }
    return false;
}

/**
 * Checks if the current entity is a user or not.
 *
 * @return bool Returns true if it is a user, otherwise false.
 */
function isUser(): bool
{
    $authorization_data = getAuthorizationData();
    if (property_exists($authorization_data, "login_id")) {
        return true;
    }
    return false;
}

/**
 * Checks if the current entity is logged in or not.
 *
 * @return bool Returns true if logged in, otherwise false.
 */
function isLoggedIn(): bool
{
    global $key, $alg;
    $jwt = getBearerToken();
    if (isset($jwt)) {
        try {
            $decoded = JWT::decode($jwt, $key, array($alg));
            if ($decoded->exp > time()) {
                return true;
            }
        } catch (Exception $e) {
        }
    }
    return false;
}
