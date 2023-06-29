<?php

// Developers desktop/workstation

// Error reporting
$settings["error"]["display_error_details"] = true;
error_reporting(E_ALL);
ini_set("display_errors", "1");

// Database
$settings["db"]["database"] = "spacehuddle";
$settings["db"]["host"] = "localhost";
$settings["db"]["username"] = "root";
$settings["db"]["password"] = "";

//$settings["jwt"]["issuer"] = "localhost";

// Application
$settings["application"]["baseUrl"] = "http://localhost:8080";
