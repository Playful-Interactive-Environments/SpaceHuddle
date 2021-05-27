<?php
require_once('AuthorizedUserTestCase.php');

class TopicTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstSessionId();
    $this->getFirstTopicId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$this->sessionId/topics/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->topicId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'title' => 'test topic',
      'description' => '...'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/session/$this->sessionId/topic/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'title' => 'test topic',
      'description' => 'new description',
      'active_task_id' => null
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/topic/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/topic/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
