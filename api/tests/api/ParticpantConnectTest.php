<?php
require_once('AuthorizedParticipantTestCase.php');

class ParticpantConnectTest extends AuthorizedTestCase
{
  public function test_workflow() {
    $data =  json_encode((object)array(
        'session_key' => 'ZP4L4QFX',
        'ip_hash' => 'xxx'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/participant/connect/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $access_token = $this->to_json($res->getBody())->access_token;
    $bearer = "Bearer $access_token";

    $res = $this->client->delete($this->get_absolute_api_url("/api/participant/"), [
        'headers' => ["Authorization" => $bearer]
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

}

?>
