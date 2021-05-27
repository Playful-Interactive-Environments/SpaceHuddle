<?php
require_once('AuthorizedTestCase.php');

use PieLab\GAB\Models\TaskType;

abstract class AuthorizedParticipantTestCase extends AuthorizedTestCase
{
  protected ?string $topicId;
  protected ?string $taskIdIdea;
  protected ?string $taskIdVote;
  protected ?string $votingID;
  protected ?string $ideaId;

  public function getFirstTopicId() : ?string {
    if (!isset($this->topicId)) {
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/topics/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->topicId = $result[0]->id;
      }
    }
    return $this->topicId;
  }

  protected function getFirstTaskId($task_type) : ?string {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/tasks/"));
    $result = $this->toJSON($res->getBody());
    if (count($result) > 0) {
      foreach ($result as $resultItem) {
        if (strtoupper($resultItem->task_type) == strtoupper($task_type)) {
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

  public function getFirstTaskIdVote() : ?string {
    if (!isset($this->taskIdVote)) {
      $this->taskIdVote = $this->getFirstTaskId(TaskType::VOTING);
    }
    return $this->taskIdVote;
  }

  public function getFirstVotingId() : ?string {
    if (!isset($this->votingID)) {
      $task_id = $this->getFirstTaskIdVote();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$task_id/votings/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->votingID = $result[0]->id;
      }
    }
    return $this->votingID;
  }

  public function getFirstIdeaId() : ?string {
    if (!isset($this->ideaId)) {
      $task_id = $this->getFirstTaskIdIdea();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$task_id/ideas/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->ideaId = $result[0]->id;
      }
    }
    return $this->ideaId;
  }

  protected function getAccessToken() : ?string {
    $client = new GuzzleHttp\Client();

    $login_data =  json_encode((object)array(
      'session_key' => 'ZP4L4QFX',
      'ip_hash' => 'localhost'
    ));

    $res = $client->post($this->getAbsoluteApiUrl("/api/participant/connect/"), [
        'body' => $login_data
    ]);
    $access_token = $this->toJSON($res->getBody())->access_token;
    return $access_token;
  }
}

?>
