<?php
require_once('AuthorizedUserTestCase.php');

class SelectionTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
    $this->getFirstSelectionId();
    $this->getFirstIdeaId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/selections/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->selectionId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/selection/$this->selectionId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetGroupIdeas() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/selection/$this->selectionId/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'name' => 'test selection'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/topic/$this->topicId/selection/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'name' => 'test selection 2'
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/selection/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/selection/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflowGroupIdeas() {
    $data =  json_encode(array(
      $this->ideaId
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/selection/$this->selectionId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/selection/$this->selectionId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
