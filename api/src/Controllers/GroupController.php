<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Group;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\StateIdea;
use PieLab\GAB\Models\TaskType;

/**
 * Controller class for category.
 * @package PieLab\GAB\Controllers
 */
class GroupController extends IdeaController
{
    /**
     * Creates a new GroupController.
     */
    public function __construct()
    {
        parent::__construct("idea", Group::class, TaskController::class, "task", "task_id", "category");
        $this->taskType = TaskType::CATEGORISATION;
    }

    /**
     * Get a list of all categories for the task.
     * @param string|null $taskId The task's ID.
     * @param bool $treatParticipantsSeparately Treat each participant separately.
     * @return string Returns a json encoded list of all categories for this task.
     * @OA\Get(
     *   path="/api/task/{taskId}/categories/",
     *   summary="List of all categories for the task.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Category")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAllFromTask(?string $taskId = null, bool $treatParticipantsSeparately = false): string
    {
        return parent::readAllFromTask($taskId, $treatParticipantsSeparately);
    }

    /**
     * Get a list of all categories for the topic.
     * @param string|null $topicId The topic's ID.
     * @param bool $treatParticipantsSeparately Treat each participant separately.
     * @return string Returns a json encoded list of all categories for this topic.
     * @OA\Get(
     *   path="/api/topic/{topicId}/categories/",
     *   summary="List of all categories for the topic.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Category")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAllFromTopic(?string $topicId = null, bool $treatParticipantsSeparately = false): string
    {
        return parent::readAllFromTopic($topicId, $treatParticipantsSeparately);
    }

    /**
     * Read detailed data for the category with the specified ID.
     * @param string|null $id The category's ID.
     * @param bool $treatParticipantsSeparately Treat participants separately.
     * @return string Returns a json encoded entry for this category.
     * @OA\Get(
     *   path="/api/category/{id}/",
     *   summary="Detail data for the category with the specified id.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="id", description="ID of category to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Category"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null, bool $treatParticipantsSeparately = false): string
    {
        return parent::read($id, $treatParticipantsSeparately);
    }

    /**
     * Create a category for a task.
     * @param string|null $taskId The task ID.
     * @param string|null $keywords The category's keywords.
     * @param string|null $description The category's description.
     * @param string|null $link The category's link.
     * @param string|null $image The category's image.
     * @return string Returns the inserted category data in JSON format.
     * @OA\Post(
     *   path="/api/task/{taskId}/category/",
     *   summary="Create a new category for the task.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"keywords"},
     *         @OA\Property(property="keywords", type="string"),
     *         @OA\Property(property="description", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Category"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function addToTask(
        ?string $taskId = null,
        ?string $keywords = null,
        ?string $description = null,
        ?string $link = null,
        ?string $image = null
    ): string {
        $state = strtoupper(StateIdea::NEW);
        $params = $this->formatParameters(
            [
                "task_id" => ["default" => $taskId, "url" => "task", "required" => true],
                "keywords" => ["default" => $keywords, "required" => true],
                "description" => ["default" => $description],
                "link" => ["default" => $link],
                "image" => ["default" => $image],
                "state" => ["default" => $state]
            ]
        );

        return $this->addGeneric($params->task_id, $params, authorizedRoles: [Role::MODERATOR, Role::FACILITATOR]);
    }

    /**
     * Create a new category for the topic.
     * @param string|null $topicId The topic ID.
     * @param string|null $keywords The category's keywords.
     * @param string|null $description The category's description.
     * @param string|null $link The category's link.
     * @param string|null $image The category's image.
     * @return string Returns the inserted category data in JSON format.
     * @OA\Post(
     *   path="/api/topic/{topicId}/category/",
     *   summary="Create a new category for the topic.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"keywords"},
     *         @OA\Property(property="keywords", type="string"),
     *         @OA\Property(property="description", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Category"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function addToTopic(
        ?string $topicId = null,
        ?string $keywords = null,
        ?string $description = null,
        ?string $link = null,
        ?string $image = null
    ): string {
        return parent::addToTopic($topicId, $keywords, $description, $link, $image);
    }

    /**
     * Update a category.
     * @param string|null $id The category's ID.
     * @param string|null $state The category's state.
     * @param string|null $keywords The category's keywords.
     * @param string|null $description The category's description.
     * @param string|null $link The category's link.
     * @param string|null $image The category's image.
     * @return string
     * @OA\Put(
     *   path="/api/category/",
     *   summary="Update a category.",
     *   tags={"Category"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/Category")
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Category"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(
        ?string $id = null,
        ?string $state = null,
        ?string $keywords = null,
        ?string $description = null,
        ?string $link = null,
        ?string $image = null
    ): string {
        return parent::update($id, $state, $keywords, $description, $link, $image);
    }

    /**
     * Delete a grcategoryoup.
     * @param string|null $id The ID.
     * @return string A JSON encoded response statement.
     * @OA\Delete(
     *   path="/api/category/{id}/",
     *   summary="Delete a category.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="id", description="ID of category to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        return parent::delete($id);
    }
}
