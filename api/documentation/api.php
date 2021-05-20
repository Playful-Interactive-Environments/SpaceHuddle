<?php

require(__DIR__ . '/../vendor/autoload.php');

header('Content-Type: application/json');

echo OpenApi\Generator::scan(
    [
        __DIR__ . '/../controllers',
        __DIR__ . '/../models'
    ]
)->toJSON();
