<?php
require_once('AuthorizedUserTestCase.php');

class ResourceTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstSessionId();
    $this->getFirstResourceId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$this->sessionId/resources/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->resourceId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/resource/$this->resourceId/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'title' => 'test resource',
      'link' => '...'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/session/$this->sessionId/resource/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'title' => 'test resource',
      'link' => 'www.image.at'
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/resource/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/resource/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
