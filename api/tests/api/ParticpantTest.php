<?php
require_once('AuthorizedParticipantTestCase.php');

class ParticpantTest extends AuthorizedParticipantTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
  }

  public function testGetTopics() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/topics/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetTasks() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetTopicTasks() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/participant_tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
