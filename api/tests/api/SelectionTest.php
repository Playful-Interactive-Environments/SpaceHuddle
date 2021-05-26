<?php
require_once('AuthorizedUserTestCase.php');

class SelectionTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
    $this->getFirstSelectionId();
    $this->get_first_idea_id();
  }

  public function testGetAll() {
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/selections/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->selectionId);
    $res = $this->client->get($this->get_absolute_api_url("/api/selection/$this->selectionId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetGroupIdeas() {
    $res = $this->client->get($this->get_absolute_api_url("/api/selection/$this->selectionId/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'name' => 'test selection'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/topic/$this->topic_id/selection/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'name' => 'test selection 2'
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/selection/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/selection/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflowGroupIdeas() {
    $data =  json_encode(array(
      $this->idea_id
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/selection/$this->selectionId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/selection/$this->selectionId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
