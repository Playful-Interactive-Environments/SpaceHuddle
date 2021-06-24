<?php
require_once('AuthorizedUserTestCase.php');

class GroupTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
    $this->getFirstTaskIdGroup();
    $this->getFirstGroupId();
    $this->getFirstIdeaId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/categories/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$this->taskIdGroup/categories/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->groupId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/category/$this->groupId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetGroupIdeas() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/category/$this->groupId/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'keywords' => 'test group',
      'description' => "..."
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/topic/$this->topicId/category/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/category/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/task/$this->taskIdGroup/category/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'keywords' => 'test group',
      'description' => "new description"
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/category/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/category/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflowGroupIdeas() {
    $data =  json_encode(array(
      $this->ideaId
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/category/$this->groupId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/category/$this->groupId/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
