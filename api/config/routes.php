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
use App\Action\Hierarchy\HierarchyCreateAction;
use App\Action\Hierarchy\HierarchyDeleteAction;
use App\Action\Hierarchy\HierarchyReadAllAction;
use App\Action\Hierarchy\HierarchyReadSingleAction;
use App\Action\Hierarchy\HierarchyUpdateAction;
use App\Action\Home\HomeAction;
use App\Action\Idea\IdeaCreateForTopicAction;
use App\Action\Idea\IdeaDeleteAction;
use App\Action\Idea\IdeaReadAllFromTaskAction;
use App\Action\Idea\IdeaCreateForTaskAction;
use App\Action\Idea\IdeaReadAllFromTopicAction;
use App\Action\Idea\IdeaReadSingleAction;
use App\Action\Idea\IdeaUpdateAction;
use App\Action\Module\ModuleCreateAction;
use App\Action\Module\ModuleDeleteAction;
use App\Action\Module\ModuleReadAllAction;
use App\Action\Module\ModuleReadSingleAction;
use App\Action\Module\ModuleUpdateAction;
use App\Action\Module\UsedModuleNamesReadAction;
use App\Action\OpenApi\Version1DocAction;
use App\Action\Participant\ParticipantConnectAction;
use App\Action\Participant\ParticipantDeleteAction;
use App\Action\Participant\ParticipantReadTaskAction;
use App\Action\Participant\ParticipantReadTopicAction;
use App\Action\Participant\ParticipantReconnectAction;
use App\Action\Participant\ParticipantUpdateAction;
use App\Action\PreflightAction;
use App\Action\Resource\ResourceCreateAction;
use App\Action\Resource\ResourceDeleteAction;
use App\Action\Resource\ResourceReadAllAction;
use App\Action\Resource\ResourceReadSingleAction;
use App\Action\Resource\ResourceUpdateAction;
use App\Action\Selection\SelectionCreateAction;
use App\Action\Selection\SelectionDeleteAction;
use App\Action\Selection\SelectionIdeaAddAction;
use App\Action\Selection\SelectionIdeaDeleteAction;
use App\Action\Selection\SelectionIdeaReadAction;
use App\Action\Selection\SelectionReadAllAction;
use App\Action\Selection\SelectionReadSingleAction;
use App\Action\Selection\SelectionUpdateAction;
use App\Action\Session\PublicScreenReadAction;
use App\Action\Session\PublicScreenUpdateAction;
use App\Action\Session\SessionDeleteAction;
use App\Action\Session\SessionParticipantReadAction;
use App\Action\Session\SessionReadInfosAction;
use App\Action\Session\SessionUpdateAction;
use App\Action\SessionRole\SessionRoleCreateAction;
use App\Action\SessionRole\SessionRoleDeleteAction;
use App\Action\SessionRole\SessionRoleDeleteOwnAction;
use App\Action\SessionRole\SessionRoleReadAllAction;
use App\Action\SessionRole\SessionRoleReadSingleAction;
use App\Action\SessionRole\SessionRoleUpdateAction;
use App\Action\Task\TaskCreateAction;
use App\Action\Task\TaskDeleteAction;
use App\Action\Task\TaskReadAllAction;
use App\Action\Task\TaskReadDependentAction;
use App\Action\Task\TaskReadSingleAction;
use App\Action\Task\TaskStateUpdateAction;
use App\Action\Task\TaskUpdateAction;
use App\Action\Topic\TopicCreateAction;
use App\Action\Topic\TopicDeleteAction;
use App\Action\Topic\TopicReadAllAction;
use App\Action\Topic\TopicReadSingleAction;
use App\Action\Topic\TopicUpdateAction;
use App\Action\Tutorial\TutorialCreateAction;
use App\Action\Tutorial\TutorialReadAllAction;
use App\Action\User\UserChangePasswordAction;
use App\Action\User\UserConfirmAction;
use App\Action\User\UserDeleteAction;
use App\Action\User\UserForgetPasswordAction;
use App\Action\User\UserLoginAction;
use App\Action\User\UserRegisterAction;
use \App\Action\Session\SessionCreateAction;
use \App\Action\Session\SessionReadSingleAction;
use \App\Action\Session\SessionReadAllAction;
use App\Action\User\UserResetPasswordAction;
use App\Action\User\UserSendConfirmAction;
use App\Action\View\ViewReadAllAction;
use App\Action\View\ViewReadSingleAction;
use App\Action\Vote\VoteCreateAction;
use App\Action\Vote\VoteDeleteAction;
use App\Action\Vote\VoteHierarchyReadAllAction;
use App\Action\Vote\VoteHierarchyResultReadAction;
use App\Action\Vote\VoteReadAllAction;
use App\Action\Vote\VoteReadSingleAction;
use App\Action\Vote\VoteResultReadAction;
use App\Action\Vote\VoteParentResultReadAction;
use App\Action\Vote\VoteUpdateAction;
use App\Action\Topic\TopicExportAction;
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
            $app->put("/forget-password[/]", UserForgetPasswordAction::class);
            $app->put("/reset/{email}[/]", UserResetPasswordAction::class);
            $app->put("/send-confirm/{email}[/]", UserSendConfirmAction::class);
            $app->put("/confirm/{token}[/]", UserConfirmAction::class);
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
        "/session_infos",
        function (RouteCollectorProxy $app) {
            $app->post("[/]", SessionReadInfosAction::class);
        }
    );

    $app->group(
        "/sessions",
        function (RouteCollectorProxy $app) {
            $app->get("[/]", SessionReadAllAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/session",
        function (RouteCollectorProxy $app) {
            $app->put("/{sessionId}/public_screen/{taskId}[/]", PublicScreenUpdateAction::class);
            $app->get("/{sessionId}/public_screen[/]", PublicScreenReadAction::class);

            $app->post("/{sessionId}/topic[/]", TopicCreateAction::class);
            $app->get("/{sessionId}/topics[/]", TopicReadAllAction::class);

            $app->post("/{sessionId}/resource[/]", ResourceCreateAction::class);
            $app->get("/{sessionId}/resources[/]", ResourceReadAllAction::class);

            $app->post("/{sessionId}/authorized_user[/]", SessionRoleCreateAction::class);
            $app->put("/{sessionId}/authorized_user[/]", SessionRoleUpdateAction::class);
            $app->delete("/{sessionId}/authorized_user/{username}[/]", SessionRoleDeleteAction::class);
            $app->get("/{sessionId}/own_user_role[/]", SessionRoleReadSingleAction::class);
            $app->get("/{sessionId}/authorized_users[/]", SessionRoleReadAllAction::class);
            $app->delete("/{sessionId}/own_user_role[/]", SessionRoleDeleteOwnAction::class);

            $app->get("/{sessionId}/participants[/]", SessionParticipantReadAction::class);

            $app->post("[/]", SessionCreateAction::class);
            $app->get("/{id}[/]", SessionReadSingleAction::class);
            $app->put("[/]", SessionUpdateAction::class);
            $app->delete("/{id}[/]", SessionDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/topic",
        function (RouteCollectorProxy $app) {
            $app->get("/{topicId}/views[/]", ViewReadAllAction::class);

            $app->post("/{topicId}/task[/]", TaskCreateAction::class);
            $app->get("/{topicId}/tasks[/]", TaskReadAllAction::class);

            $app->get("/{topicId}/ideas[/]", IdeaReadAllFromTopicAction::class);
            $app->post("/{topicId}/idea[/]", IdeaCreateForTopicAction::class);

            $app->get("/{topicId}/categories[/]", CategoryReadAllFromTopicAction::class);
            $app->post("/{topicId}/category[/]", CategoryCreateForTopicAction::class);

            $app->get("/{topicId}/selections[/]", SelectionReadAllAction::class);
            $app->post("/{topicId}/selection[/]", SelectionCreateAction::class);

            $app->get("/{id}[/]", TopicReadSingleAction::class);
            $app->put("[/]", TopicUpdateAction::class);
            $app->delete("/{id}[/]", TopicDeleteAction::class);

            $app->get("/{id}/export/{exportType}[/]", TopicExportAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/task",
        function (RouteCollectorProxy $app) {
            $app->put("/{taskId}/client_application_state/{state}[/]", TaskStateUpdateAction::class);

            $app->get("/{taskId}/modules[/]", ModuleReadAllAction::class);
            $app->post("/{taskId}/module[/]", ModuleCreateAction::class);

            $app->get("/{taskId}/ideas[/]", IdeaReadAllFromTaskAction::class);
            $app->post("/{taskId}/idea[/]", IdeaCreateForTaskAction::class);

            $app->get("/{taskId}/categories[/]", CategoryReadAllFromTaskAction::class);
            $app->post("/{taskId}/category[/]", CategoryCreateForTaskAction::class);

            $app->get("/{taskId}/hierarchies/{parentHierarchyId}[/]", HierarchyReadAllAction::class);
            $app->get("/{taskId}/hierarchies[/]", HierarchyReadAllAction::class);
            $app->post("/{taskId}/hierarchy[/]", HierarchyCreateAction::class);

            $app->get("/{taskId}/votes[/]", VoteReadAllAction::class);
            $app->get("/{taskId}/vote_result[/]", VoteResultReadAction::class);
            $app->get("/{taskId}/vote_result_parent[/]", VoteParentResultReadAction::class);
            $app->post("/{taskId}/vote[/]", VoteCreateAction::class);

            $app->get("/{id}[/]", TaskReadSingleAction::class);
            $app->get("/{id}/dependent[/]", TaskReadDependentAction::class);
            $app->put("[/]", TaskUpdateAction::class);
            $app->delete("/{id}[/]", TaskDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/module",
        function (RouteCollectorProxy $app) {
            $app->get("/{id}[/]", ModuleReadSingleAction::class);
            $app->put("[/]", ModuleUpdateAction::class);
            $app->delete("/{id}[/]", ModuleDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/module_names",
        function (RouteCollectorProxy $app) {
            $app->get("/{taskType}[/]", UsedModuleNamesReadAction::class);
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

    $app->group(
        "/hierarchy",
        function (RouteCollectorProxy $app) {
            $app->get("/{parentId}/vote_result[/]", VoteHierarchyResultReadAction::class);
            $app->get("/{parentId}/votes[/]", VoteHierarchyReadAllAction::class);

            $app->get("/{id}[/]", HierarchyReadSingleAction::class);
            $app->put("[/]", HierarchyUpdateAction::class);
            $app->delete("/{id}[/]", HierarchyDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/selection",
        function (RouteCollectorProxy $app) {
            $app->get("/{selectionId}/ideas[/]", SelectionIdeaReadAction::class);
            $app->post("/{selectionId}/ideas[/]", SelectionIdeaAddAction::class);
            $app->delete("/{selectionId}/ideas[/]", SelectionIdeaDeleteAction::class);

            $app->get("/{id}[/]", SelectionReadSingleAction::class);
            $app->put("[/]", SelectionUpdateAction::class);
            $app->delete("/{id}[/]", SelectionDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/view",
        function (RouteCollectorProxy $app) {
            $app->get("/{type}/{typeId}[/]", ViewReadSingleAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/vote",
        function (RouteCollectorProxy $app) {
            $app->get("/{id}[/]", VoteReadSingleAction::class);
            $app->put("[/]", VoteUpdateAction::class);
            $app->delete("/{id}[/]", VoteDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/resource",
        function (RouteCollectorProxy $app) {
            $app->get("/{id}[/]", ResourceReadSingleAction::class);
            $app->put("[/]", ResourceUpdateAction::class);
            $app->delete("/{id}[/]", ResourceDeleteAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/tutorial_steps",
        function (RouteCollectorProxy $app) {
            $app->get("[/]", TutorialReadAllAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $app->group(
        "/tutorial_step",
        function (RouteCollectorProxy $app) {
            $app->post("[/]", TutorialCreateAction::class);
        }
    )->add(JwtAuthMiddleware::class);

    $generateOptions();
};
