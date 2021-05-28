<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Selection;

/**
 * Controller for selections.
 * @package PieLab\GAB\Controllers
 */
class SelectionController extends AbstractController
{
    /**
     * Creates a new SelectionController.
     */
    protected function __construct()
    {
        parent::__construct(
            "selection_group",
            Selection::class,
            TopicController::class,
            "topic",
            "topic_id",
            "selection"
        );
    }

    /**
     * List all selections for a topic.
     * @param string|null $topicId The selection's ID.
     * @return string A list of all selections for a topic in JSON format.
     * @OA\Get(
     *   path="/api/topic/{topicId}/selections/",
     *   summary="List of all selections for the topic.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Selection")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(?string $topicId = null): string
    {
        return parent::readAllGeneric($topicId);
    }

    /**
     * Read detailed data for a selection with the specified ID.
     * @param string|null $id The selection's ID.
     * @return string The data in JSON format.
     * @OA\Get(
     *   path="/api/selection/{id}/",
     *   summary="Detail data for the selection with the specified id.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="id", description="ID of selection to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Selection"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null): string
    {
        return parent::readGeneric($id);
    }

    /**
     * Create a new selection for a topic.
     * @param string|null $topicId The topic's ID.
     * @param string|null $name The selection name.
     * @return string The selection in JSON format.
     * @OA\Post(
     *   path="/api/topic/{topicId}/selection/",
     *   summary="Create a new selection for the topic.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"name"},
     *         @OA\Property(property="name", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Selection"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(?string $topicId = null, ?string $name = null): string
    {
        $params = $this->formatParameters(
            [
                "topic_id" => ["default" => $topicId, "url" => "topic", "required" => true],
                "name" => ["default" => $name, "required" => true]
            ]
        );

        return $this->addGeneric($params->topic_id, $params);
    }

    /**
     * Update a selection.
     * @param string|null $id The selection's ID.
     * @param string|null $name The selection's name.
     * @return string The updated data in JSON format.
     * @OA\Put(
     *   path="/api/selection/",
     *   summary="Update a selection.",
     *   tags={"Selection"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="json",
     *         @OA\Schema(ref="#/components/schemas/Selection")
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Selection"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(?string $id = null, ?string $name = null): string
    {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "name" => ["default" => $name]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }

    /**
     * Delete a selection.
     * @param string|null $id The selection's ID.
     * @return string Returns a success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/selection/{id}/",
     *   summary="Delete a selection.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="id", description="ID of selection to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id): void
    {
        $query = "DELETE FROM selection_group_idea WHERE selection_group_id = :selection_group_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":selection_group_id", $id);
        $statement->execute();
    }
}
