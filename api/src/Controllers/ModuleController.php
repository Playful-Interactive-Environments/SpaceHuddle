<?php

namespace PieLab\GAB\Controllers;

/**
 * Controller for modules.
 * @package PieLab\GAB\Controllers
 */
class ModuleController extends AbstractController
{
    /**
     * Creates a new TopicController.
     */
    protected function __construct()
    {
        parent::__construct("module", Module::class, TaskController::class, "task", "task_id");
    }

    /**
     * Delete data.
     * @param string $id Primary key of the linked table entry.
     */
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
        $query = "DELETE FROM module_state WHERE module_id = :module_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":module_id", $id);
        $statement->execute();

        $query = "UPDATE task
          SET active_module_id = null
          WHERE active_module_id = :module_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":module_id", $id);
        $statement->execute();

        $query = "UPDATE session 
          SET public_screen_module_id = null
          WHERE public_screen_module_id = :module_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":module_id", $id);
        $statement->execute();
    }
}
