<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Task;

/**
 * Controller for the public screen.
 * @package PieLab\GAB\Controllers
 */
class PublicScreenController extends AbstractController
{
    /**
     * Creates a new PublicScreenController.
     */
    protected function __construct()
    {
        parent::__construct("task", Task::class, TopicController::class, "topic", "topic_id");
    }

    /**
     * Set a task to be displayed on the public screen for the session.
     * @param string|null $sessionId The session ID.
     * @param string|null $taskId The task ID.
     * @return string Returns the success message in JSON format.
     * @OA\Post(
     *   path="/api/session/{session_id}/public_screen/",
     *   summary="Set a task to be displayed on the public screen for the session.",
     *   tags={"Public Screen"},
     *   @OA\Parameter(in="path", name="session_id", description="ID of the session to be updated", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"task_id"},
     *         @OA\Property(property="task_id", type="string", format="int")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function setPublicScreen(?string $sessionId = null, string $taskId = null): string
    {
        $loginId = Authorization::getAuthorizationProperty("login_id");
        #TODO: check rights for session
    }

    /**
     * Get the aktive task to be displayed on the public screen for the session.
     * @param string|null $sessionId The session ID.
     * @param string|null $taskId The task ID.
     * @return string Returns the success message in JSON format.
     * @OA\Get(
     *   path="/api/session/{session_id}/public_screen/",
     *   summary="Get the aktive task to be displayed on the public screen for the session.",
     *   tags={"Public Screen"},
     *   @OA\Parameter(in="path", name="session_id", description="ID of the session to be displayed", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Task"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function getPublicScreen(?string $sessionId = null, ?string $taskId = null): string
    {
        $loginId = Authorization::getAuthorizationProperty("login_id");
        #TODO: check rights for session
    }
}
