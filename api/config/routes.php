<?php

// Define app routes

use App\Action\Home\HomeAction;
use App\Action\OpenApi\Version1DocAction;
use App\Action\User\UserChangePasswordAction;
use App\Action\User\UserDeleteAction;
use App\Action\User\UserLoginAction;
use App\Action\User\UserRegisterAction;
use \App\Action\Session\SessionCreateAction;
use \App\Action\Session\SessionReadSingleAction;
use \App\Action\Session\SessionReadAllAction;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Redirect to Swagger documentation
    $app->get("/", HomeAction::class)->setName("home");

    // Swagger API documentation
    $app->get("/docs/v1", Version1DocAction::class)->setName("docs");

    //$app->get("/user/register", \App\Action\User\UserRegisterAction::class);

    // Password protected area
    $app->group(
        "/user",
        function (RouteCollectorProxy $app) {
            /*$app->get("/users", \App\Action\User\UserFindAction::class);
            $app->post("/users", \App\Action\User\UserCreateAction::class);
            $app->get("/users/{user_id}", \App\Action\User\UserReadAction::class);
            $app->put("/users/{user_id}", \App\Action\User\UserUpdateAction::class);
            $app->delete("/users/{user_id}", \App\Action\User\UserDeleteAction::class);
            $app->post("/user/login/", \App\Action\User\UserLoginAction::class);*/
            $app->post("/login[/]", UserLoginAction::class);
            $app->post("/register[/]", UserRegisterAction::class);
        }
    );

    // Password protected area
    $app->group(
        "/user",
        function (RouteCollectorProxy $app) {
            $app->put("[/]", UserChangePasswordAction::class);
            $app->delete("[/]", UserDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/session",
        function (RouteCollectorProxy $app) {
            $app->post("[/]", SessionCreateAction::class);
            $app->get("/{id}[/]", SessionReadSingleAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/sessions",
        function (RouteCollectorProxy $app) {
            $app->get("[/]", SessionReadAllAction::class);
        }
    )->add(JwtAuthMiddleware::class);
};
