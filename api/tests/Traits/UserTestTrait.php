<?php

namespace App\Test\Traits;

/**
 * User Test Trait.
 */
trait UserTestTrait
{
    use EntityTestTrait;

    /**
     * Get the first session entry from the database
     * @param string|null $token Use alternative Login
     * @return object|null First entry
     */
    private function getFirstSession(string|null $token = null) : ?object
    {
        return $this->getFirstEntity(
            "sessions",
            [
                "title" => "php unit test session",
                "description" => "create from unit test",
                "maxParticipants" => 100,
                "expirationDate" => "2022-01-01"
            ],
            $token
        );
    }

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionId() : ?string
    {
        $result = $this->getFirstSession();
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first session id
     * @return string|null json token
     */
    protected function getFirstSessionKey() : ?string
    {
        $result = $this->getFirstSession();
        if (is_object($result) and property_exists($result, "connectionKey")) {
            return $result->connectionKey;
        }
        return "";
    }

    /**
     * Determine first topic id
     * @return string|null json token
     */
    protected function getFirstTopicId() : ?string
    {
        $sessionId = $this->getFirstSessionId();
        $result = $this->getFirstEntity(
            "session/$sessionId/topics",
            [
                "title" => "php unit test topic",
                "description" => "create from unit test"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "BRAINSTORMING",
                "name" => "php unit test task"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstBrainstormingTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "BRAINSTORMING",
                "name" => "php unit test task"
            ],
            condition: [
                "taskType" => "BRAINSTORMING"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstCategorisationTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "CATEGORISATION",
                "name" => "php unit test task"
            ],
            condition: [
                "taskType" => "CATEGORISATION"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstSelectionTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "SELECTION",
                "name" => "php unit test task"
            ],
            condition: [
                "taskType" => "SELECTION"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstVotingTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "VOTING",
                "name" => "php unit test task"
            ],
            condition: [
                "taskType" => "VOTING"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first task id
     * @return string|null json token
     */
    protected function getFirstInformationTaskId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/tasks",
            [
                "taskType" => "INFORMATION",
                "name" => "php unit test task"
            ]
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first idea id
     * @return string|null json token
     */
    protected function getFirstIdeaId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/ideas"
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }

    /**
     * Determine first category id
     * @return string|null json token
     */
    protected function getFirstCategoryId() : ?string
    {
        $topicId = $this->getFirstTopicId();
        $result = $this->getFirstEntity(
            "topic/$topicId/categories"
        );
        if (is_object($result) and property_exists($result, "id")) {
            return $result->id;
        }
        return "";
    }
}
