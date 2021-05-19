<?php
require_once('authorized_participant_test_case.php');

class Particpant_Test extends AuthorizedParticipantTestCase
{
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
}

?>
