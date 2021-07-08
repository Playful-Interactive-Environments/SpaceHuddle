<?php

// Define app routes

use App\Action\Category\CategoryCreateForTaskAction;
use App\Action\Category\CategoryCreateForTopicAction;
use App\Action\Category\CategoryDeleteAction;
use App\Action\Category\CategoryIdeaAddAction;
use App\Action\Category\CategoryIdeaDeleteAction;
use App\Action\Category\CategoryIdeaReadAction;
use App\Action\Category\CategoryReadAllFromTaskAction;
use App\Action\Category\CategoryReadAllFromTopicAction;
use App\Action\Category\CategoryReadSingleAction;
use App\Action\Category\CategoryUpdateAction;
use App\Action\Home\HomeAction;
use App\Action\Idea\IdeaCreateForTopicAction;
use App\Action\Idea\IdeaDeleteAction;
use App\Action\Idea\IdeaReadAllFromTaskAction;
use App\Action\Idea\IdeaCreateForTaskAction;
use App\Action\Idea\IdeaReadAllFromTopicAction;
use App\Action\Idea\IdeaReadSingleAction;
use App\Action\Idea\IdeaUpdateAction;
use App\Action\OpenApi\Version1DocAction;
use App\Action\Participant\ParticipantConnectAction;
use App\Action\Participant\ParticipantDeleteAction;
use App\Action\Participant\ParticipantReadTaskAction;
use App\Action\Participant\ParticipantReadTopicAction;
use App\Action\Participant\ParticipantReconnectAction;
use App\Action\Participant\ParticipantUpdateAction;
use App\Action\PreflightAction;
use App\Action\Session\SessionDeleteAction;
use App\Action\Session\SessionUpdateAction;
use App\Action\Task\TaskCreateAction;
use App\Action\Task\TaskDeleteAction;
use App\Action\Task\TaskReadAllAction;
use App\Action\Task\TaskReadSingleAction;
use App\Action\Task\TaskStateUpdateAction;
use App\Action\Task\TaskUpdateAction;
use App\Action\Topic\TopicCreateAction;
use App\Action\Topic\TopicDeleteAction;
use App\Action\Topic\TopicReadAllAction;
use App\Action\Topic\TopicReadSingleAction;
use App\Action\Topic\TopicUpdateAction;
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
    $generateOptions = function () use ($app) {
        $routes = $app->getRouteCollector()->getRoutes();
        $optionPatterns = [];
        foreach ($routes as $route) {
            $pattern = $route->getPattern();
            if (!in_array($pattern, $optionPatterns)) {
                array_push($optionPatterns, $pattern);
            }
        }

        foreach ($optionPatterns as $pattern) {
            $app->options($pattern, PreflightAction::class);
        }
    };

    // Redirect to Swagger documentation
    $app->get("/", HomeAction::class)->setName("home");

    // Swagger API documentation
    $app->get("/docs/v1", Version1DocAction::class)->setName("docs");

    $app->group(
        "/user",
        function (RouteCollectorProxy $app) {
            $app->post("/login[/]", UserLoginAction::class);
            $app->post("/register[/]", UserRegisterAction::class);
        }
    );

    $app->group(
        "/participant",
        function (RouteCollectorProxy $app) {
            $app->post("/connect[/]", ParticipantConnectAction::class);
            $app->delete("[/]", ParticipantDeleteAction::class);
            $app->get("/tasks[/]", ParticipantReadTaskAction::class);
            $app->get("/topics[/]", ParticipantReadTopicAction::class);
            $app->get("/reconnect/{browserKey}[/]", ParticipantReconnectAction::class);
            $app->put("/state/{state}[/]", ParticipantUpdateAction::class);
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
        "/sessions",
        function (RouteCollectorProxy $app) {
            $app->get("[/]", SessionReadAllAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/session",
        function (RouteCollectorProxy $app) {
            $app->post("/{sessionId}/topic[/]", TopicCreateAction::class);
            $app->get("/{sessionId}/topics[/]", TopicReadAllAction::class);

            $app->post("[/]", SessionCreateAction::class);
            $app->get("/{id}[/]", SessionReadSingleAction::class);
            $app->put("[/]", SessionUpdateAction::class);
            $app->delete("/{id}[/]", SessionDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/topic",
        function (RouteCollectorProxy $app) {
            $app->post("/{topicId}/task[/]", TaskCreateAction::class);
            $app->get("/{topicId}/tasks[/]", TaskReadAllAction::class);

            $app->get("/{topicId}/ideas[/]", IdeaReadAllFromTopicAction::class);
            $app->post("/{topicId}/idea[/]", IdeaCreateForTopicAction::class);

            $app->get("/{topicId}/categories[/]", CategoryReadAllFromTopicAction::class);
            $app->post("/{topicId}/category[/]", CategoryCreateForTopicAction::class);

            $app->get("/{id}[/]", TopicReadSingleAction::class);
            $app->put("[/]", TopicUpdateAction::class);
            $app->delete("/{id}[/]", TopicDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/task",
        function (RouteCollectorProxy $app) {
            $app->put("/{taskId}/client_application_state/{state}[/]", TaskStateUpdateAction::class);

            $app->get("/{taskId}/ideas[/]", IdeaReadAllFromTaskAction::class);
            $app->post("/{taskId}/idea[/]", IdeaCreateForTaskAction::class);

            $app->get("/{taskId}/categories[/]", CategoryReadAllFromTaskAction::class);
            $app->post("/{taskId}/category[/]", CategoryCreateForTaskAction::class);

            $app->get("/{id}[/]", TaskReadSingleAction::class);
            $app->put("[/]", TaskUpdateAction::class);
            $app->delete("/{id}[/]", TaskDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/idea",
        function (RouteCollectorProxy $app) {
            $app->get("/{id}[/]", IdeaReadSingleAction::class);
            $app->put("[/]", IdeaUpdateAction::class);
            $app->delete("/{id}[/]", IdeaDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/category",
        function (RouteCollectorProxy $app) {
            $app->get("/{categoryId}/ideas[/]", CategoryIdeaReadAction::class);
            $app->post("/{categoryId}/ideas[/]", CategoryIdeaAddAction::class);
            $app->delete("/{categoryId}/ideas[/]", CategoryIdeaDeleteAction::class);

            $app->get("/{id}[/]", CategoryReadSingleAction::class);
            $app->put("[/]", CategoryUpdateAction::class);
            $app->delete("/{id}[/]", CategoryDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $generateOptions();
};
