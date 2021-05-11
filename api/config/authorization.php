<?php
require_once __DIR__.'/../vendor/autoload.php';
use \Firebase\JWT\JWT;

// show error reporting
error_reporting(E_ALL);

// set your default time-zone
date_default_timezone_set('Europe/Vienna');

// variables used for jwt
$key = "gab";
$issued_at = time();
$expiration_time = $issued_at + (60 * 60 * 24); // valid for 24 hour
$issuer = "http://localhost/api/";
$alg = 'HS256';

function generateToken($data)
{
  global $issued_at, $expiration_time, $issuer, $key, $alg;
  $token = array(
     "iat" => $issued_at,
     "exp" => $expiration_time,
     "iss" => $issuer,
     "data" => $data
  );
  // generate jwt
  $jwt = JWT::encode($token, $key, $alg);
  return $jwt;
}

/**
 * Get header Authorization
 * */
function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
/**
 * get access token from header
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function getAuthorizationData()
{
  global $issued_at, $expiration_time, $issuer, $key, $alg;
  $jwt = getBearerToken();
  $error_msg = "no token specified";
  if (isset($jwt)) {
    $error_msg = "token expired";
    try {
      $decoded = JWT::decode($jwt, $key, array($alg));
      if ($decoded->exp > time())
        return $decoded->data;
    }
    catch(Exception $e){
      $error_msg = "wrong token";
    }
  }
  http_response_code(401);
  $error = json_encode(
    array(
      "state"=>"Unauthorized",
      "message"=>$error_msg
    )
  );
  die($error);
}

function getAuthorizationProperty($property)
{
  $authorization_data = getAuthorizationData();
  if (property_exists ($authorization_data , $property)) {
    return $authorization_data->$property;
  }
  http_response_code(401);
  $error = json_encode(
    array(
      "state"=>"Wrong Authorization Type",
      "message"=>"Property '".$property."' not exists in authorization header"
    )
  );
  die($error);
}

function isParticipant()
{
  $authorization_data = getAuthorizationData();
  if (property_exists ($authorization_data , "participant_id")) {
    return true;
  }
  return false;
}
?>
