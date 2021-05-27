<?php

namespace PieLab\GAB\Controllers;

use Exception;
use PieLab\GAB\Models\Resource;

/**
 * Controller for resources.
 * @package PieLab\GAB\Controllers
 */
class ResourceController extends AbstractController
{
    /**
     * Creates a new ResourceController.
     */
    protected function __construct()
    {
        parent::__construct("resource", Resource::class, SessionController::class, "session", "session_id");
    }

    /**
     * List of all resources for the session.
     * @return string The resource data of this session.
     * @OA\Get(
     *   path="/api/session/{session_id}/resources/",
     *   summary="List of all resources for the session.",
     *   tags={"Resource"},
     *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Resource"))
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(): string
    {
        #TODO: check rights for session
    }

    /**
     * Detail data for the resource with the specified id.
     * @return string Detail data for the specified resource.
     * @OA\Get(
     *   path="/api/resource/{id}/",
     *   summary="Detail data for the resource with the specified id.",
     *   tags={"Resource"},
     *   @OA\Parameter(in="path", name="id", description="ID of resource to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Resource"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null): string
    {
        #TODO: check rights for session
    }

    /**
     * Create a new resource for the session.
     * @return string Data of the added resource in JSON format.
     * @OA\Post(
     *   path="/api/session/{session_id}/resource/",
     *   summary="Create a new resource for the session.",
     *   tags={"Resource"},
     *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"title"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="link", type="string"),
     *         @OA\Property(property="image", type="string", format="binary")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Resource"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(): string
    {
        try {
            #TODO: check rights for session
        } catch (Exception $e) {
            http_response_code(404);
            $error_msg = $e->getMessage();
            $this->connection->rollBack();
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => 'Error occurred:' . $error_msg
                ]
            );
            die($error);
            #return $error;
        }
    }

    /**
     * Update a resource.
     * @param string|null $id The resource's id.
     * @return string The updated data in JSON format.
     * @OA\Put(
     *   path="/api/resource/",
     *   summary="Update a resource.",
     *   tags={"Resource"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="json",
     *         @OA\Schema(ref="#/components/schemas/Resource")
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Resource"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(?string $id = null): string
    {
        #TODO: check rights for session
    }

    /**
     * Delete a resource.
     * @param string|null $id The resource's ID.
     * @return string Returns the success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/resource/{id}/",
     *   summary="Delete a resource.",
     *   tags={"Resource"},
     *   @OA\Parameter(in="path", name="id", description="ID of resource to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        #TODO: check rights for session
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id): void
    {
    }
}
