<?php

/**
 * Environment specific application configuration.
 *
 * You should store all secret information (username, password, tokens, private keys) here.
 *
 * Make sure the env.php file is added to your .gitignore
 * so it is not checked-in the code
 *
 * Place the env.php _outside_ the project root directory, to protect against
 * overwriting at deployment.
 *
 * This usage ensures that no sensitive passwords or API keys will
 * ever be in the version control history so there is less risk of
 * a security breach, and production values will never have to be
 * shared with all project collaborators.
 */

$settings["jwt"]["private_key"] = file_get_contents(__DIR__ . "/../resources/keys/private.pem");
$settings["jwt"]["public_key"] = file_get_contents(__DIR__ . "/../resources/keys/public.pem");

// Authorization
$settings['auth']['model'] = __DIR__ . "/../resources/auth/model.conf";
$settings['auth']['policy'] = __DIR__ . "/../resources/auth/policy.csv";

// Error reporting
$settings["error"]["display_error_details"] = true;

// Database
$settings["db"]["database"] = "spacehuddle";
$settings["db"]["host"] = "localhost";
$settings["db"]["username"] = "root";
$settings["db"]["password"] = "";

// Application
$settings["application"]["baseUrl"] = "http://localhost:8080";
$settings["application"]["forgetPassword"] = "/forget-password/";
$settings["application"]["session"] = "/session/";
$settings["application"]["confirm"] = "/confirm-email/";
$settings["application"]["name"] = "spacehuddle.io";

// EMail
$settings["application"]["email"] = "info@spacehuddle.io";
$settings["smtp"] = [
    "type" => "smtp",
    "host" => "smtp.mailtrap.io',",
    "port" => "25",
    "username" => "my-username",
    "password" => "my-username-password",
];
