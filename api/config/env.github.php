<?php

require __DIR__ . "/local.test.php";

// Database
$settings["db"]["username"] = "root";
$settings["db"]["password"] = "";

$settings["jwt"]["private_key"] = file_get_contents(__DIR__ . "/../resources/keys/private.pem");
$settings["jwt"]["public_key"] = file_get_contents(__DIR__ . "/../resources/keys/public.pem");
