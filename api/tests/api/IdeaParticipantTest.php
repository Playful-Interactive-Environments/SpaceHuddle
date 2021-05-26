<?php
require_once('AuthorizedParticipantTestCase.php');
require_once('IdeaTest.php');
use PieLab\GAB\Models\StateIdea;

class IdeaParticipantTest extends AuthorizedParticipantTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
    $this->get_first_task_id_idea();
    #$user = new Idea_Test();
    #$this->topic_id = $user->get_first_topic_id();
    #$this->task_id_idea = $user->get_first_task_id_idea();
  }

  public function test_workflow() {
    $data =  json_encode((object)array(
      'keywords' => 'test idea',
      'description' => "...",
      'link' => null,
      'image' => null
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/topic/$this->topic_id/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $res = $this->client->delete($this->get_absolute_api_url("/api/idea/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->post($this->get_absolute_api_url("/api/task/$this->task_id_idea/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'state' => StateIdea::HANDLED,
      'keywords' => 'test idea',
      'description' => "new description",
      'link' => null,
      'image' => null
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/idea/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
