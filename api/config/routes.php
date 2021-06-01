<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\HttpBasicAuthentication;

return function (App $app) {
    // Redirect to Swagger documentation
    $app->get("/", \App\Action\Home\HomeAction::class)->setName("home");

    // Swagger API documentation
    $app->get("/docs/v1", \App\Action\OpenApi\Version1DocAction::class)->setName("docs");

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
            $app->post("/login[/]", \App\Action\User\UserLoginAction::class);
            $app->post("/register[/]", \App\Action\User\UserRegisterAction::class);
            $app->put("", \App\Action\User\UserChangePasswordAction::class);
            $app->delete("", \App\Action\User\UserDeleteAction::class);
        }
    );//->add(HttpBasicAuthentication::class);
};
