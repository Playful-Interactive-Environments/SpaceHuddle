<?php
require_once('AuthorizedUserTestCase.php');

class IdeaTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
    $this->getFirstTaskIdIdea();
    $this->getFirstIdeaId();
  }

  public function testGtAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$this->taskIdIdea/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->ideaId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/idea/$this->ideaId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
