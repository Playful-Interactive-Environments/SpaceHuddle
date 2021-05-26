<?php
require_once('AuthorizedParticipantTestCase.php');

class ParticpantTest extends AuthorizedParticipantTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
  }

  public function test_get_topics() {
    $res = $this->client->get($this->get_absolute_api_url("/api/participant/topics/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_tasks() {
    $res = $this->client->get($this->get_absolute_api_url("/api/participant/tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_topic_tasks() {
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/participant_tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
