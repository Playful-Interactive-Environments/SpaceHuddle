<?php

require "../vendor/autoload.php";

header("Content-Type: application/json");

echo OpenApi\Generator::scan(
    [
        __DIR__ . "/../src/Controllers",
        __DIR__ . "/../src/Models",
        __DIR__ . "/../controllers",
        __DIR__ . "/../models"
    ]
)->toJSON();
