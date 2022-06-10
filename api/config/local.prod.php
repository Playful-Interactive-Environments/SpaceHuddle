<?php

// Production environment

$settings["error"]["display_error_details"] = true;
$settings["logger"]["level"] = \Monolog\Logger::INFO;

// Database
$settings["db"]["database"] = "spacehuddle";

// Application
$settings["application"]["baseUrl"] = "https://spacehuddle.io";
