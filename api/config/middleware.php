<?php

use App\Middleware\AuthorizationMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\JwtClaimMiddleware;
use Selective\BasePath\BasePathMiddleware;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;

return function (App $app) {
    $app->addBodyParsingMiddleware();
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(CorsMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(JwtClaimMiddleware::class);
    $app->add(BasePathMiddleware::class);
    $app->add(AuthorizationMiddleware::class);
    $app->add(ErrorMiddleware::class);
};
