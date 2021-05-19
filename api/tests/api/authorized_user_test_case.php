<?php
require_once('authorized_test_case.php');
require_once(__DIR__.'/../../models/task_type.php');

abstract class AuthorizedUserTestCase extends AuthorizedTestCase
{
  protected $session_id;
  protected $topic_id;
  protected $task_id_idea;
  protected $task_id_group;
  protected $task_id_select;
  protected $task_id_vote;
  protected $idea_id;
  protected $group_id;

  public function __construct()
  {
    parent::__construct();
    $this->get_first_session_id();
  }

  public function get_first_session_id() {
    if (!isset($this->session_id)) {
      $res = $this->client->get($this->get_absolute_api_url("/api/sessions/"));
      $result = $this->to_json($res->getBody());
      if (count($result) > 0) {
        $this->session_id = $result[0]->id;
      }
    }
    return $this->session_id;
  }

  public function get_first_topic_id() {
    if (!isset($this->topic_id)) {
      $session_id = $this->get_first_session_id();
      $res = $this->client->get($this->get_absolute_api_url("/api/session/$session_id/topics/"));
      $result = $this->to_json($res->getBody());
      if (count($result) > 0) {
        $this->topic_id = $result[0]->id;
      }
    }
    return $this->topic_id;
  }

  protected function get_first_task_id($task_type) {
    $topic_id = $this->get_first_topic_id();
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$topic_id/tasks/"));
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

  public function get_first_task_id_group() {
    if (!isset($this->task_id_group)) {
      $this->task_id_group = $this->get_first_task_id(Task_Type::GROUPING);
    }
    return $this->task_id_group;
  }

  public function get_first_task_id_select() {
    if (!isset($this->task_id_select)) {
      $this->task_id_select = $this->get_first_task_id(Task_Type::SELECTION);
    }
    return $this->task_id_select;
  }

  public function get_first_task_id_vote() {
    if (!isset($this->task_id_vote)) {
      $this->task_id_vote = $this->get_first_task_id(Task_Type::VOTING);
    }
    return $this->task_id_vote;
  }

  public function get_first_idea_id() {
    if (!isset($this->idea_id)) {
      $task_id = $this->get_first_task_id_idea();
      $res = $this->client->get($this->get_absolute_api_url("/api/task/$task_id/ideas/"));
      $result = $this->to_json($res->getBody());
      if (count($result) > 0) {
        $this->idea_id = $result[0]->id;
      }
    }
    return $this->idea_id;
  }

  public function get_first_group_id() {
    if (!isset($this->group_id)) {
      $task_id = $this->get_first_task_id_group();
      $res = $this->client->get($this->get_absolute_api_url("/api/task/$task_id/groups/"));
      $result = $this->to_json($res->getBody());
      if (count($result) > 0) {
        $this->group_id = $result[0]->id;
      }
    }
    return $this->group_id;
  }

  protected function get_access_token() {
    $client = new GuzzleHttp\Client();

    $login_data =  json_encode((object)array(
      'username' => 'testuser',
      'password' => 'testuser'
    ));

    $res = $client->post($this->get_absolute_api_url("/api/user/login/"), [
        'body' => $login_data
    ]);
    $access_token = $this->to_json($res->getBody())->access_token;
    return $access_token;
  }
}

?>
