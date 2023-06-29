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
require __DIR__ . "/local.dev.php";
require __DIR__ . "/local.all.php";

$settings["jwt"]["private_key"] = file_get_contents(__DIR__ . "/../resources/keys/private.pem");
$settings["jwt"]["public_key"] = file_get_contents(__DIR__ . "/../resources/keys/public.pem");

// Authorization
$settings['auth']['model'] = __DIR__ . "/../resources/auth/model.conf";
$settings['auth']['policy'] = __DIR__ . "/../resources/auth/policy.csv";
