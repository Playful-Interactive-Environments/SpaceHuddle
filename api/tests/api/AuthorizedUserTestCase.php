<?php
require_once('AuthorizedTestCase.php');

use PieLab\GAB\Models\TaskType;

abstract class AuthorizedUserTestCase extends AuthorizedTestCase
{
  protected ?string $sessionId;
  protected ?string $topicId;
  protected ?string $taskIdIdea;
  protected ?string $taskIdGroup;
  protected ?string $taskIdSelect;
  protected ?string $taskIdVote;
  protected ?string $ideaId;
  protected ?string $groupId;
  protected ?string $selectionId;
  protected ?string $resourceId;

  public function __construct()
  {
    parent::__construct();
    $this->getFirstSessionId();
  }

  public function getFirstSessionId() : ?string {
    if (!isset($this->sessionId)) {
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/sessions/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->sessionId = $result[0]->id;
      }
    }
    return $this->sessionId;
  }

  public function getFirstTopicId() : ?string {
    if (!isset($this->topicId)) {
      $sessionId = $this->getFirstSessionId();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$sessionId/topics/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->topicId = $result[0]->id;
      }
    }
    return $this->topicId;
  }

  protected function getFirstTaskId($taskType) : ?string {
    $topicId = $this->getFirstTopicId();
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$topicId/tasks/"));
    $result = $this->toJSON($res->getBody());
    if (count($result) > 0) {
      foreach ($result as $resultItem) {
        if (strtoupper($resultItem->taskType) == strtoupper($taskType)) {
          return $resultItem->id;
        }
      }
    }
    return null;
  }

  public function getFirstTaskIdIdea() : ?string {
    if (!isset($this->taskIdIdea)) {
      $this->taskIdIdea = $this->getFirstTaskId(TaskType::BRAINSTORMING);
    }
    return $this->taskIdIdea;
  }

  public function getFirstTaskIdGroup() : ?string {
    if (!isset($this->taskIdGroup)) {
      $this->taskIdGroup = $this->getFirstTaskId(TaskType::GROUPING);
    }
    return $this->taskIdGroup;
  }

  public function getFirstTaskIdSelect() : ?string {
    if (!isset($this->taskIdSelect)) {
      $this->taskIdSelect = $this->getFirstTaskId(TaskType::SELECTION);
    }
    return $this->taskIdSelect;
  }

  public function getFirstTaskIdVote() : ?string {
    if (!isset($this->taskIdVote)) {
      $this->taskIdVote = $this->getFirstTaskId(TaskType::VOTING);
    }
    return $this->taskIdVote;
  }

  public function getFirstIdeaId() : ?string {
    if (!isset($this->ideaId)) {
      $taskId = $this->getFirstTaskIdIdea();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$taskId/ideas/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->ideaId = $result[0]->id;
      }
    }
    return $this->ideaId;
  }

  public function getFirstGroupId() : ?string {
    if (!isset($this->groupId)) {
      $taskId = $this->getFirstTaskIdGroup();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$taskId/groups/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->groupId = $result[0]->id;
      }
    }
    return $this->groupId;
  }

  public function getFirstSelectionId() : ?string {
    if (!isset($this->selectionId)) {
      $topicId = $this->getFirstTopicId();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$topicId/selections/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->selectionId = $result[0]->id;
      }
    }
    return $this->selectionId;
  }

  public function getFirstResourceId() : ?string {
    if (!isset($this->resourceId)) {
      $sessionId = $this->getFirstSessionId();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$sessionId/resources/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->resourceId = $result[0]->id;
      }
    }
    return $this->resourceId;
  }

  protected function getAccessToken() : ?string {
    $client = new GuzzleHttp\Client();

    $loginData =  json_encode((object)array(
      'username' => 'testuser',
      'password' => 'testuser'
    ));

    $res = $client->post($this->getAbsoluteApiUrl("/api/user/login/"), [
        'body' => $loginData
    ]);
    return $this->toJSON($res->getBody())->accessToken;
  }
}

?>
