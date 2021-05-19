<?php
require_once('authorized_test_case.php');

abstract class AuthorizedParticipantTestCase extends AuthorizedTestCase
{
  protected $topic_id;
  protected $task_id_idea;

  public function get_first_topic_id() {
    if (!isset($this->topic_id)) {
      $res = $this->client->get($this->get_absolute_api_url("/api/participant/topics/"));
      $result = $this->to_json($res->getBody());
      if (count($result) > 0) {
        $this->topic_id = $result[0]->id;
      }
    }
    return $this->topic_id;
  }

  protected function get_first_task_id($task_type) {
    $res = $this->client->get($this->get_absolute_api_url("/api/participant/tasks/"));
    $result = $this->to_json($res->getBody());
    if (count($result) > 0) {
      foreach ($result as $result_item) {
        if (strtoupper($result_item->task_type) == strtoupper($task_type)) {
          return $result_item->id;
        }
      }
    }
    return null;
  }

  public function get_first_task_id_idea() {
    if (!isset($this->task_id_idea)) {
      $this->task_id_idea = $this->get_first_task_id(Task_Type::BRAINSTORMING);
    }
    return $this->task_id_idea;
  }

  protected function get_access_token() {
    $client = new GuzzleHttp\Client();

    $login_data =  json_encode((object)array(
      'session_key' => 'ZP4L4QFX',
      'ip_hash' => 'localhost'
    ));

    $res = $client->post($this->get_absolute_api_url("/api/participant/connect/"), [
        'body' => $login_data
    ]);
    $access_token = $this->to_json($res->getBody())->access_token;
    return $access_token;
  }
}

?>
