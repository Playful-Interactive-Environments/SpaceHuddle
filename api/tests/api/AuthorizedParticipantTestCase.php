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

  protected function getFirstTaskId($taskType) : ?string {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/tasks/"));
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

  public function getFirstTaskIdVote() : ?string {
    if (!isset($this->taskIdVote)) {
      $this->taskIdVote = $this->getFirstTaskId(TaskType::VOTING);
    }
    return $this->taskIdVote;
  }

  public function getFirstVotingId() : ?string {
    if (!isset($this->votingID)) {
      $taskId = $this->getFirstTaskIdVote();
      $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$taskId/votings/"));
      $result = $this->toJSON($res->getBody());
      if (count($result) > 0) {
        $this->votingID = $result[0]->id;
      }
    }
    return $this->votingID;
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

  protected function getAccessToken() : ?string {
    $client = new GuzzleHttp\Client();

    $loginData =  json_encode((object)array(
      'sessionKey' => '9064AWOU',# 'ZP4L4QFX',
      'ip' => 'localhost'
    ));

    $res = $client->post($this->getAbsoluteApiUrl("/api/participant/connect/"), [
        'body' => $loginData
    ]);
    return $this->toJSON($res->getBody())->accessToken;
  }
}

?>
