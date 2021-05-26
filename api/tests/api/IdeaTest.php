<?php
require_once('AuthorizedUserTestCase.php');
use PieLab\GAB\Models\StateTask;

class IdeaTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
    $this->get_first_task_id_idea();
    $this->get_first_idea_id();
  }

  public function test_get_all() {
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $res = $this->client->get($this->get_absolute_api_url("/api/task/$this->task_id_idea/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_by_id() {
    $this->assertIsString($this->idea_id);
    $res = $this->client->get($this->get_absolute_api_url("/api/idea/$this->idea_id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
